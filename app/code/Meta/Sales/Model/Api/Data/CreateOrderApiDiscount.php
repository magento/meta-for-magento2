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

namespace Meta\Sales\Model\Api\Data;

use Magento\Framework\DataObject;
use Meta\Sales\Api\Data\CreateOrderApiDiscountInterface;

class CreateOrderApiDiscount extends DataObject implements CreateOrderApiDiscountInterface
{

    /**
     * Gets a coupon code
     *
     * @return string
     */
    public function getCouponCode(): string
    {
        return $this->_getData(self::DATA_COUPON_CODE);
    }

    /**
     * Sets a coupon code
     *
     * @param  string $couponCode
     * @return CreateOrderApiDiscountInterface
     */
    public function setCouponCode(string $couponCode): CreateOrderApiDiscountInterface
    {
        return $this->setData(self::DATA_COUPON_CODE, $couponCode);
    }

    /**
     * Gets the discount amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->_getData(self::DATA_AMOUNT);
    }

    /**
     * Sets the discount amount
     *
     * @param  float $amount
     * @return CreateOrderApiDiscountInterface
     */
    public function setAmount(float $amount): CreateOrderApiDiscountInterface
    {
        return $this->setData(self::DATA_AMOUNT, $amount);
    }

    /**
     * Gets the discount source
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->_getData(self::DATA_SOURCE);
    }

    /**
     * Sets discount source
     *
     * @param  string $source
     * @return CreateOrderApiDiscountInterface
     */
    public function setSource(string $source): CreateOrderApiDiscountInterface
    {
        return $this->setData(self::DATA_SOURCE, $source);
    }
}
