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

namespace Meta\Sales\Api\Data;

/**
 * Model for the discount payload sent to create order api
 */
interface CreateOrderApiDiscountInterface
{
    public const DATA_COUPON_CODE = "couponCode";
    public const DATA_AMOUNT = "amount";
    public const DATA_SOURCE = "source";

    /**
     * Get coupon code
     *
     * @return string
     */
    public function getCouponCode(): string;

    /**
     * Set coupon code
     *
     * @param string $couponCode
     * @return CreateOrderApiDiscountInterface
     */
    public function setCouponCode(string $couponCode): CreateOrderApiDiscountInterface;

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount(): float;

    /**
     * Set amount
     *
     * @param float $amount
     * @return CreateOrderApiDiscountInterface
     */
    public function setAmount(float $amount): CreateOrderApiDiscountInterface;

    /**
     * Get source
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Set source
     *
     * @param string $source
     * @return CreateOrderApiDiscountInterface
     */
    public function setSource(string $source): CreateOrderApiDiscountInterface;
}
