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
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CreateOrder
     */
    private CreateOrder $createOrder;

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
     * @param LoggerInterface $logger
     * @param CreateOrder $createOrder
     */
    public function __construct(
        GraphAPIAdapter $graphAPIAdapter,
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        CreateOrder $createOrder
    ) {
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->createOrder = $createOrder;
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
        $pageId = $this->systemConfig->getPageId($storeId);
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $ordersData = $this->graphAPIAdapter->getOrders($pageId, $cursorAfter);

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
                $this->logger->critical($e->getMessage());
            }
        }
        if (!empty($orderIds)) {
            $this->graphAPIAdapter->acknowledgeOrders($pageId, $orderIds);
        }

        if (isset($ordersData['paging']['next'])) {
            $this->pullOrders($storeId, $ordersData['paging']['cursors']['after']);
        }
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
