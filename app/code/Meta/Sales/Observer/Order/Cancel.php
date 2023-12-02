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

namespace Meta\Sales\Observer\Order;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Order\CreateCancellation;

class Cancel implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * Constructor
     *
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        SystemConfig    $systemConfig,
        GraphAPIAdapter $graphAPIAdapter,
        OrderHelper     $orderHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Cancel facebook order
     *
     * @param Observer $observer
     * @return void
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();

        if (!($this->systemConfig->isOrderSyncEnabled($storeId)
            && $this->systemConfig->isOnsiteCheckoutEnabled($storeId))) {
            return;
        }

        $historyItems = $order->getStatusHistoryCollection();
        foreach ($historyItems as $historyItem) {
            $comment = $historyItem->getComment();
            if ($comment && strpos($comment, CreateCancellation::CANCELLATION_NOTE) !== false) {
                // No-op if order was originally canceled on Facebook -- avoid infinite cancel loop.
                return;
            }
        }

        $this->orderHelper->setFacebookOrderExtensionAttributes($order);
        $facebookOrderId = $order->getExtensionAttributes()->getFacebookOrderId();
        if (!$facebookOrderId) {
            return;
        }

        $this->cancelOrder((int)$storeId, $facebookOrderId);

        $order->addCommentToStatusHistory('Order Canceled on Meta');
    }

    /**
     * Perform cancel of a facebook order via api
     *
     * @param int $storeId
     * @param string $fbOrderId
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    private function cancelOrder(int $storeId, string $fbOrderId)
    {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));
        try {
            $this->graphAPIAdapter->cancelOrder($fbOrderId);
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
}
