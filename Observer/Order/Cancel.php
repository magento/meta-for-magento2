<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Order;

use Exception;
use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
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
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveOrderSync())) {
            return;
        }

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        $facebookOrderId = $order->getExtensionAttributes()->getFacebookOrderId();
        if (!$facebookOrderId) {
            return;
        }

        $this->commerceHelper->cancelOrder($facebookOrderId);
        $order->addCommentToStatusHistory("Cancelled order on Facebook.");
    }
}
