<?php

declare(strict_types=1);

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\Sales\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Model\Order\CreateOrder;
use Meta\Sales\Model\Order\CreateRefund;
use Meta\Sales\Model\Order\CreateCancellation;
use Meta\BusinessExtension\Helper\FBEHelper;

/**
 * Helper class to sync (to magento), mark shipped, cancel,
 * and refund facebook orders
 */
class CommerceHelper
{
    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CreateOrder
     */
    private CreateOrder $createOrder;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var CreateRefund
     */
    private CreateRefund $createRefund;

    /**
     * @var CreateCancellation
     */
    private CreateCancellation $createCancellation;

    /**
     * @var int
     */
    private int $ordersPulledTotal = 0;

    /**
     * @var Order[]
     */
    private array $ordersCreated = [];

    /**
     * @var array
     */
    private array $exceptions = [];

    /**
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param SystemConfig $systemConfig
     * @param CreateOrder $createOrder
     * @param CreateRefund $createRefund
     * @param CreateCancellation $createCancellation
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        GraphAPIAdapter    $graphAPIAdapter,
        SystemConfig       $systemConfig,
        CreateOrder        $createOrder,
        CreateRefund       $createRefund,
        CreateCancellation $createCancellation,
        FBEHelper          $fbeHelper
    ) {
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->createOrder = $createOrder;
        $this->createRefund = $createRefund;
        $this->fbeHelper = $fbeHelper;
        $this->createCancellation = $createCancellation;
    }

    /**
     * Get facebook orders via api and create magento orders from them
     *
     * @param int $storeId
     * @param false|string $cursorAfter
     * @return void
     * @throws GuzzleException
     */
    public function pullOrders(int $storeId, $cursorAfter = false)
    {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $ordersRootId = $this->systemConfig->getCommerceAccountId($storeId) ?: $this->systemConfig->getPageId($storeId);
        $ordersData = $this->graphAPIAdapter->getOrders($ordersRootId, $cursorAfter);

        $this->ordersPulledTotal += count($ordersData['data']);

        $orderIds = [];
        foreach ($ordersData['data'] as $orderData) {
            try {
                $facebookOrderId = $orderData['id'];
                $magentoOrder = $this->createOrder->execute($orderData, $storeId);
                $this->ordersCreated[] = $magentoOrder;
                $orderIds[$magentoOrder->getIncrementId()] = $facebookOrderId;
            } catch (Exception $e) {
                $this->exceptions[] = $e->getMessage();
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'order_sync',
                        'event_type' => 'create_magento_orders'
                    ]
                );
            }
        }
        if (!empty($orderIds)) {
            $this->graphAPIAdapter->acknowledgeOrders($ordersRootId, $orderIds);
        }
        if (isset($ordersData['paging']['next'])) {
            $this->pullOrders($storeId, $ordersData['paging']['cursors']['after']);
        }
    }

    /**
     * Pulls orders that have refunds and fetches the corresponding refund details.
     *
     * @param int $storeId Store ID for which the refund orders need to be pulled.
     * @return array Associative array containing the refund details with Facebook Order IDs as keys.
     * @throws GuzzleException
     */
    public function pullRefundOrders(int $storeId): array
    {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        // Pull orders with refunds
        $ordersRootId = $this->systemConfig->getCommerceAccountId($storeId) ?: $this->systemConfig->getPageId($storeId);
        $ordersWithRefunds = $this->graphAPIAdapter->getOrders(
            $ordersRootId,
            false,
            GraphAPIAdapter::ORDER_FILTER_REFUNDS
        );
        $refundOrdersDetails = [];

        foreach ($ordersWithRefunds['data'] as $orderData) {
            try {
                $facebookOrderId = $orderData['id'];
                // Get refund details for each order
                $refundDetails = $this->graphAPIAdapter->getRefunds($facebookOrderId)[0];
                $this->createRefund->execute($orderData, $refundDetails, $storeId);
                $refundOrdersDetails[$facebookOrderId] = $refundDetails;
            } catch (Exception $e) {
                $this->exceptions[] = $e->getMessage();
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'order_sync',
                        'event_type' => 'create_magento_orders'
                    ]
                );
            }
        }
        return $refundOrdersDetails;
    }

    /**
     * Pulls orders that are cancelled and fetches the corresponding cancellation details.
     *
     * @param int $storeId Store ID for which the cancelled orders need to be pulled.
     * @return array Associative array containing the cancellation details with Facebook Order IDs as keys.
     * @throws GuzzleException
     */
    public function pullCancelledOrders(int $storeId): array
    {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        // Pull orders with cancellations
        $ordersRootId = $this->systemConfig->getCommerceAccountId($storeId) ?: $this->systemConfig->getPageId($storeId);
        $ordersWithCancellations = $this->graphAPIAdapter->getOrders(
            $ordersRootId,
            false,
            GraphAPIAdapter::ORDER_FILTER_CANCELLATIONS
        );
        $cancelledOrdersDetails = [];

        foreach ($ordersWithCancellations['data'] as $orderData) {
            try {
                $facebookOrderId = $orderData['id'];
                // Get cancellation details for each order (assuming a method exists in GraphAPIAdapter)
                $cancellationDetails = $this->graphAPIAdapter->getCancellations($facebookOrderId)[0];
                // Process cancellations (you would need to create a method to handle this)
                $this->createCancellation->execute($orderData, $cancellationDetails, $storeId);
                $cancelledOrdersDetails[$facebookOrderId] = $cancellationDetails;
            } catch (Exception $e) {
                $this->exceptions[] = $e->getMessage();
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'order_sync',
                        'event_type' => 'process_cancellations'
                    ]
                );
            }
        }
        return $cancelledOrdersDetails;
    }

    /**
     * Pull pending facebook orders
     *
     * @param int $storeId
     * @return array
     * @throws GuzzleException
     */
    public function pullPendingOrders(int $storeId): array
    {
        $this->ordersPulledTotal = 0;
        $this->ordersCreated = [];
        $this->exceptions = [];
        $this->pullOrders($storeId);
        return [
            'total_orders_pulled' => $this->ordersPulledTotal,
            'total_orders_created' => count($this->ordersCreated),
            'exceptions' => $this->exceptions
        ];
    }
}
