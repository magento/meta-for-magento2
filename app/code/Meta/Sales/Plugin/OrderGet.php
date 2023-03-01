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

namespace Meta\Sales\Plugin;

use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
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
     * After get order plugin
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $subject
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
