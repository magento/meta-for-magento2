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
use Meta\Sales\Api\Data\CreateOrderApiShipmentDetailsInterface;

class CreateOrderApiShipmentDetails extends DataObject implements CreateOrderApiShipmentDetailsInterface
{
    /**
     * Retrieves the shipping address.
     *
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    public function getShippingAddress(): \Magento\Quote\Api\Data\AddressInterface
    {
        return $this->_getData(self::DATA_SHIPPING_ADDRESS);
    }

    /**
     * Sets the shipping address.
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $shippingAddress
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingAddress(
        \Magento\Quote\Api\Data\AddressInterface $shippingAddress
    ): CreateOrderApiShipmentDetailsInterface {
        return $this->setData(self::DATA_SHIPPING_ADDRESS, $shippingAddress);
    }

    /**
     * Retrieves the shipping method.
     *
     * @return string
     */
    public function getShippingMethod(): string
    {
        return $this->_getData(self::DATA_SHIPPING_METHOD);
    }

    /**
     * Sets the shipping method.
     *
     * @param  string $shippingMethod
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingMethod(string $shippingMethod): CreateOrderApiShipmentDetailsInterface
    {
        return $this->setData(self::DATA_SHIPPING_METHOD, $shippingMethod);
    }

    /**
     * Retrieves the shipping total.
     *
     * @return float
     */
    public function getShippingTotal(): float
    {
        return $this->_getData(self::DATA_SHIPPING_TOTAL);
    }

    /**
     * Sets the shipping total.
     *
     * @param  float $shippingTotal
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingTotal(float $shippingTotal): CreateOrderApiShipmentDetailsInterface
    {
        return $this->setData(self::DATA_SHIPPING_TOTAL, $shippingTotal);
    }

    /**
     * Retrieves the shipping sub total.
     *
     * @return float
     */
    public function getShippingSubTotal(): float
    {
        return $this->_getData(self::DATA_SHIPPING_SUB_TOTAL);
    }

    /**
     * Sets the shipping sub total.
     *
     * @param  float $shippingSubTotal
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingSubTotal(float $shippingSubTotal): CreateOrderApiShipmentDetailsInterface
    {
        return $this->setData(self::DATA_SHIPPING_SUB_TOTAL, $shippingSubTotal);
    }

    /**
     * Retrieves the tax amount.
     *
     * @return float
     */
    public function getTax(): float
    {
        return $this->_getData(self::DATA_TAX);
    }

    /**
     * Sets the tax amount.
     *
     * @param  float $tax
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setTax(float $tax): CreateOrderApiShipmentDetailsInterface
    {
        return $this->setData(self::DATA_TAX, $tax);
    }

    /**
     * Retrieves the tax rate.
     *
     * @return float
     */
    public function getTaxRate(): float
    {
        return $this->_getData(self::DATA_TAX_RATE);
    }

    /**
     * Sets the tax rate.
     *
     * @param  float $taxRate
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setTaxRate(float $taxRate): CreateOrderApiShipmentDetailsInterface
    {
        return $this->setData(self::DATA_TAX_RATE, $taxRate);
    }
}
