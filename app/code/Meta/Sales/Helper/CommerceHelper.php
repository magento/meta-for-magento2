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
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Api\Data\FacebookOrderInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\Sales\Model\Order\CreateOrder;
use Meta\Sales\Model\Order\CreateRefund;
use Meta\Sales\Model\Order\CreateCancellation;
use Meta\BusinessExtension\Helper\FBEHelper;

/**
 * Helper class to sync (to magento), mark shipped, cancel,
 * and refund facebook orders
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var FacebookOrderInterfaceFactory
     */
    private FacebookOrderInterfaceFactory $facebookOrderFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

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
     * @var string[]
     */
    private array $metaOrdersCanceled = [];

    /**
     * @var Order[]
     */
    private array $ordersExisted = [];

    /**
     * @var array
     */
    private array $exceptions = [];

    /**
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param SystemConfig $systemConfig
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param OrderRepository $orderRepository
     * @param CreateOrder $createOrder
     * @param CreateRefund $createRefund
     * @param CreateCancellation $createCancellation
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        GraphAPIAdapter               $graphAPIAdapter,
        SystemConfig                  $systemConfig,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        OrderRepository               $orderRepository,
        CreateOrder                   $createOrder,
        CreateRefund                  $createRefund,
        CreateCancellation            $createCancellation,
        FBEHelper                     $fbeHelper
    ) {
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->orderRepository = $orderRepository;
        $this->createOrder = $createOrder;
        $this->createRefund = $createRefund;
        $this->fbeHelper = $fbeHelper;
        $this->createCancellation = $createCancellation;
    }

    /**
     * Get facebook order by facebook order id
     *
     * @param string $facebookOrderId
     * @return void
     */
    private function getFacebookOrder(string $facebookOrderId): FacebookOrderInterface
    {
        /** @var FacebookOrderInterface $facebookOrder */
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($facebookOrderId, 'facebook_order_id');

        return $facebookOrder;
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
                $facebookOrder = $this->getFacebookOrder($facebookOrderId);
                if ($facebookOrder->getId()) {
                    // get existing order
                    $magentoOrder = $this->orderRepository->get($facebookOrder->getMagentoOrderId());
                    $this->ordersExisted[] = $magentoOrder;
                } else {
                    // create new order
                    $magentoOrder = $this->createOrder->execute($orderData, $storeId);
                    $this->ordersCreated[] = $magentoOrder;
                }
                $orderIds[$magentoOrder->getIncrementId()] = $facebookOrderId;
            } catch (Exception $e) {
                if ($e->getMessage() === 'The requested qty is not available'
                    || $e->getMessage() === 'There are no source items with the in stock status') {
                    $this->cancelMetaOutOfStockOrder($storeId, $facebookOrderId);
                } else {
                    $this->exceptions[] = $e->getMessage();
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        [
                            'store_id' => $storeId,
                            'event' => 'order_sync',
                            'event_type' => 'create_magento_orders',
                            'order_id' => $facebookOrderId
                        ]
                    );
                }
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
     * Cancel Meta order that failed to be created due to item(s) being out of stock
     *
     * @param int $storeId
     * @param string $facebookOrderId
     * @return void
     * @throws GuzzleException
     */
    public function cancelMetaOutOfStockOrder(int $storeId, string $facebookOrderId)
    {
        try {
            $this->cancelOrder($storeId, $facebookOrderId, true);
            $this->metaOrdersCanceled[] = $facebookOrderId;
            $this->fbeHelper->logTelemetryToMeta(
                sprintf(
                    'Meta order %d cancelled due to item(s) in order being out of stock',
                    $facebookOrderId
                ),
                [
                    'store_id' => $storeId,
                    'flow_name' => 'order_sync',
                    'flow_step' => 'cancel_meta_out_of_stock_order',
                    'order_id' => $facebookOrderId
                ]
            );
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'order_sync',
                    'event_type' => 'cancel_meta_out_of_stock_order',
                    'order_id' => $facebookOrderId
                ]
            );
        }
    }

    /**
     * Perform cancel of a facebook order via api
     *
     * @param int $storeId
     * @param string $fbOrderId
     * @param array|null $items
     * @param bool $isOutOfStockCancellation
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function cancelOrder(
        int    $storeId,
        string $fbOrderId,
        array  $items = null,
        bool   $isOutOfStockCancellation = false
    ) {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));
        try {
            $this->graphAPIAdapter->cancelOrder($fbOrderId, $items, $isOutOfStockCancellation);
        } catch (GuzzleException $e) {
            $response = $e->getResponse();
            $body = json_decode((string)$response->getBody());
            throw new LocalizedException(__(
                'Error code: "%1"; Error message: "%2"',
                (string)$body->error->code,
                (string)($body->error->error_user_msg ?? $body->error->message)
            ));
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
                        'event_type' => 'create_magento_orders',
                        'order_id' => $facebookOrderId
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
                        'event_type' => 'process_cancellations',
                        'order_id' => $facebookOrderId
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
        $this->metaOrdersCanceled = [];
        $this->ordersExisted = [];
        $this->exceptions = [];
        $this->pullOrders($storeId);
        return [
            'total_orders_pulled' => $this->ordersPulledTotal,
            'total_orders_created' => count($this->ordersCreated),
            'total_meta_orders_canceled' => count($this->metaOrdersCanceled),
            'total_orders_existed' => count($this->ordersExisted),
            'exceptions' => $this->exceptions
        ];
    }
}
