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

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;

/**
 * Set Magento cart shipping option
 */
interface SetCartShippingOptionApiInterface
{
    /**
     * Set Magento cart shipping option
     *
     * @param string $externalBusinessId
     * @param string $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     * @throws \Magento\Framework\Exception\UnauthorizedTokenException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCartShippingOption(
        string $externalBusinessId,
        string $cartId,
        ShippingInformationInterface $addressInformation
    ): PaymentDetailsInterface;
}
