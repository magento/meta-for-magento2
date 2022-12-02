<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Facebook;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\FacebookOrder;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
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
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        FBEHelper $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * @param $email
     * @param $storeId
     * @return $this
     */
    public function subscribeToNewsletter($email, $storeId)
    {
        $this->fbeHelper->subscribeToNewsletter($email, $storeId);
        return $this;
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
                $this->subscribeToNewsletter($email, $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
