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

/**
 * Create Magento order
 */
interface CreateOrderApiInterface
{
    /**
     * Create order
     *
     * @param string $cartId
     * @param string $orderId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string|null $channel
     * @param bool $buyerOptin
     * @param bool $createInvoice
     * @return OrderInterface
     */
    public function createOrder(
        string  $cartId,
        string  $orderId,
        string  $email,
        string  $firstName,
        string  $lastName,
        ?string $channel,
        bool    $buyerOptin = false,
        bool    $createInvoice = false
    ): OrderInterface;
}
