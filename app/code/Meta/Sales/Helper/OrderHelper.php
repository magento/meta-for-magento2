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

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;

class OrderHelper
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private $facebookOrderFactory;

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     */
    public function __construct(
        OrderExtensionFactory         $orderExtensionFactory,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->facebookOrderFactory = $facebookOrderFactory;
    }

    public function loadFacebookOrderFromMagentoId($magentoOrderId)
    {
        /** @var \Meta\Sales\Api\Data\FacebookOrderInterface $facebookOrder */
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($magentoOrderId, 'magento_order_id');

        return $facebookOrder;
    }

    /**
     * Assign Meta order's extension attributes such as facebook_order_id to a Magento order
     *
     * @param OrderInterface $order
     * @param bool $reload
     * @return void
     */
    public function setFacebookOrderExtensionAttributes(OrderInterface $order, bool $reload = false)
    {
        // if FB order ID present, do nothing
        if ($order->getExtensionAttributes()->getFacebookOrderId() && !$reload) {
            return;
        }

        /** @var \Meta\Sales\Api\Data\FacebookOrderInterface $facebookOrder */
        $facebookOrder = $this->loadFacebookOrderFromMagentoId($order->getId());

        if (!$facebookOrder->getId()) {
            return;
        }

        $emailRemarketingOption = ($facebookOrder->getExtraData()['email_remarketing_option'] ?? false) === true;
        $syncedShipments = $facebookOrder->getSyncedShipments();

        $extensionAttributes = $order->getExtensionAttributes() ?: $this->orderExtensionFactory->create();
        $extensionAttributes->setFacebookOrderId($facebookOrder->getFacebookOrderId())
            ->setChannel($facebookOrder->getChannel())
            ->setEmailRemarketingOption($emailRemarketingOption)
            ->setSyncedShipments($syncedShipments);
        $order->setExtensionAttributes($extensionAttributes);
    }
}
