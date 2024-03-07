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

namespace Meta\Sales\Api;

use Magento\Sales\Api\Data\OrderInterface;
use Meta\Sales\Api\Data\CreateOrderApiProductItemInterface;
use Meta\Sales\Api\Data\CreateOrderApiShipmentDetailsInterface;

/**
 * Create Magento order
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
interface CreateOrderApiInterface
{
    /**
     * Create order
     *
     * @param string $cartId
     * @param string $orderId
     * @param float $orderTotal
     * @param float $taxTotal
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param CreateOrderApiProductItemInterface[] $productItems
     * @param CreateOrderApiShipmentDetailsInterface $shipmentDetails
     * @param string|null $channel
     * @param bool $buyerRemarketingOptIn
     * @param bool $createInvoice
     * @return OrderInterface
     */
    public function createOrder(
        string                                 $cartId,
        string                                 $orderId,
        float                                  $orderTotal,
        float                                  $taxTotal,
        string                                 $email,
        string                                 $firstName,
        string                                 $lastName,
        array                                  $productItems,
        CreateOrderApiShipmentDetailsInterface $shipmentDetails,
        string                                 $channel,
        bool                                   $buyerRemarketingOptIn = false,
        bool                                   $createInvoice = true
    ): OrderInterface;
}
