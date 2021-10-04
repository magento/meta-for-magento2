<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Facebook;

use Facebook\BusinessExtension\Model\FacebookOrder;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\SubscriptionManager;
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
     * @var SubscriptionManager
     */
    protected $subscriptionManager;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param SubscriptionManager $subscriptionManager
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        SubscriptionManager $subscriptionManager
    ) {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->subscriptionManager = $subscriptionManager;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var FacebookOrder $facebookOrder */
        $facebookOrder = $observer->getEvent()->getFacebookOrder();
        $storeId = $order->getStoreId();

        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }

        if (!$this->systemConfig->isAutoNewsletterSubscriptionOn($storeId)) {
            return;
        }

        try {
            $extraData = $facebookOrder->getExtraData();
            $email = $order->getCustomerEmail();
            if (isset($extraData['email_remarketing_option']) && $extraData['email_remarketing_option'] === true) {
                $this->subscriptionManager->subscribe($email, $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
