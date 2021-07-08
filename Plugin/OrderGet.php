<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Plugin;

use Facebook\BusinessExtension\Api\Data\FacebookOrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderGet
{
    /**
     * @var OrderExtensionFactory
     */
    protected $orderExtensionFactory;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    protected $facebookOrderFactory;

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        FacebookOrderInterfaceFactory $facebookOrderFactory
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->facebookOrderFactory = $facebookOrderFactory;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($order->getId(), 'magento_order_id');

        $emailRemarketingOption = ($facebookOrder->getExtraData()['email_remarketing_option'] ?? false) === true;

        if ($facebookOrder->getId()) {
            $extensionAttributes = $order->getExtensionAttributes() ?: $this->orderExtensionFactory->create();
            $extensionAttributes->setFacebookOrderId($facebookOrder->getFacebookOrderId())
                ->setChannel($facebookOrder->getChannel())
                ->setEmailRemarketingOption($emailRemarketingOption);
            $order->setExtensionAttributes($extensionAttributes);
        }

        return $order;
    }
}
