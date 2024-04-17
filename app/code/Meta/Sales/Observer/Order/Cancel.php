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

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Order\CreateCancellation;
use Meta\Sales\Helper\CommerceHelper;

use Meta\Sales\Observer\MetaObserverTrait;

class Cancel implements ObserverInterface
{
    use MetaObserverTrait;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * Constructor
     *
     * @param SystemConfig   $systemConfig
     * @param OrderHelper    $orderHelper
     * @param CommerceHelper $commerceHelper
     * @param FBEHelper      $fbeHelper
     */
    public function __construct(
        SystemConfig   $systemConfig,
        OrderHelper    $orderHelper,
        CommerceHelper $commerceHelper,
        FBEHelper      $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->orderHelper = $orderHelper;
        $this->commerceHelper = $commerceHelper;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Get Exception Event
     *
     * @return string
     */
    protected function getExceptionEvent()
    {
        return 'refund_observer_exception';
    }

    /**
     * Get Store ID
     *
     * @param  Observer $observer
     * @return string
     */
    protected function getStoreId(Observer $observer)
    {
        return $observer->getEvent()->getOrder()->getStoreId();
    }

    /**
     * Get Facebook Event Helper
     *
     * @return FBEHelper
     */
    protected function getFBEHelper()
    {
        return $this->fbeHelper;
    }

    /**
     * Cancel facebook order
     *
     * @param  Observer $observer
     * @return void
     * @throws GuzzleException
     */
    protected function executeImpl(Observer $observer)
    {
        /**
 * @var Order $order 
*/
        $order = $observer->getEvent()->getOrder();
        $storeId = $this->getStoreId($observer);

        if (!($this->systemConfig->isOrderSyncEnabled($storeId)
            && $this->systemConfig->isActiveExtension($storeId))
        ) {
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

        $this->commerceHelper->cancelOrder((int)$storeId, $facebookOrderId);

        $order->addCommentToStatusHistory('Order Canceled on Meta');
    }
}
