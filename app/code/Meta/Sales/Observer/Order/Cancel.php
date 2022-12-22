<?php
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
use Meta\Sales\Helper\CommerceHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Cancel implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CommerceHelper
     */
    private $commerceHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param CommerceHelper $commerceHelper
     */
    public function __construct(SystemConfig $systemConfig, LoggerInterface $logger, CommerceHelper $commerceHelper)
    {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->commerceHelper = $commerceHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();

        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }


        $facebookOrderId = $order->getExtensionAttributes()->getFacebookOrderId();
        if (!$facebookOrderId) {
            return;
        }

        $this->commerceHelper->setStoreId($storeId)
            ->cancelOrder($facebookOrderId);
        $order->addCommentToStatusHistory("Cancelled order on Facebook.");
    }
}
