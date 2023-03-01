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

namespace Meta\Sales\Observer\Facebook;

use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManager;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Model\FacebookOrder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class OrderCreateAfter implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * Constructor
     *
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param FBEHelper $fbeHelper
     * @param SubscriptionManager $subscriptionManager
     * @param Subscriber $subscriber
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        FBEHelper $fbeHelper,
        SubscriptionManager $subscriptionManager,
        Subscriber $subscriber
    ) {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->fbeHelper = $fbeHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->subscriber = $subscriber;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var FacebookOrder $facebookOrder */
        $facebookOrder = $observer->getEvent()->getFacebookOrder();
        $storeId = $order->getStoreId();

        if (!($this->systemConfig->isActiveExtension($storeId)
            && $this->systemConfig->isActiveOrderSync($storeId)
            && $this->systemConfig->isOnsiteCheckoutEnabled($storeId))) {
            return;
        }

        if (!$this->systemConfig->isAutoNewsletterSubscriptionOn($storeId)) {
            return;
        }

        try {
            $extraData = $facebookOrder->getExtraData();
            $email = $order->getCustomerEmail();
            if (isset($extraData['email_remarketing_option']) && $extraData['email_remarketing_option'] === true) {
                $this->subscribeToNewsletter($email, $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Subscribe to newsletter
     *
     * @param string $email
     * @param int $storeId
     * @return $this
     */
    private function subscribeToNewsletter($email, $storeId)
    {
        $subscriptionClass = '\Magento\Newsletter\Model\SubscriptionManager'; // phpcs:ignore
        if (class_exists($subscriptionClass) && method_exists($subscriptionClass, 'subscribe')) {
            $this->subscriptionManager->subscribe($email, $storeId);
        } else {
            // for older Magento versions (2.3 and below)
            $this->subscriber->subscribe($email);
        }
        return $this;
    }
}
