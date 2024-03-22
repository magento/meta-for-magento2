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
 * Model for the shipment details payload sent to createOrderApi
 */
interface CreateOrderApiShipmentDetailsInterface
{
    public const DATA_SHIPPING_ADDRESS = "shippingAddress";
    public const DATA_SHIPPING_METHOD = "shippingMethod";
    public const DATA_SHIPPING_TOTAL = "shippingTotal";
    public const DATA_SHIPPING_SUB_TOTAL = "shippingSubTotal";
    public const DATA_TAX = "tax";
    public const DATA_TAX_RATE = "taxRate";

    /**
     * Get shipping address
     *
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    public function getShippingAddress(): \Magento\Quote\Api\Data\AddressInterface;

    /**
     * Set shipping address
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $shippingAddress
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingAddress(
        \Magento\Quote\Api\Data\AddressInterface $shippingAddress
    ): CreateOrderApiShipmentDetailsInterface;

    /**
     * Get shipping method
     *
     * @return string
     */
    public function getShippingMethod(): string;

    /**
     * Set shipping method
     *
     * @param string $shippingMethod
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingMethod(string $shippingMethod): CreateOrderApiShipmentDetailsInterface;

    /**
     * Get shipping total = subtotal + tax
     *
     * @return float
     */
    public function getShippingTotal(): float;

    /**
     * Set shipping total
     *
     * @param float $shippingTotal
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingTotal(float $shippingTotal): CreateOrderApiShipmentDetailsInterface;

    /**
     * Get shipping subtotal
     *
     * @return float
     */
    public function getShippingSubTotal(): float;

    /**
     * Set shipping subtotal
     *
     * @param float $shippingSubTotal
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setShippingSubTotal(float $shippingSubTotal): CreateOrderApiShipmentDetailsInterface;

    /**
     * Get shipping tax
     *
     * @return float
     */
    public function getTax(): float;

    /**
     * Set shipping tax
     *
     * @param float $tax
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setTax(float $tax): CreateOrderApiShipmentDetailsInterface;

    /**
     * Get tax rate
     *
     * @return float
     */
    public function getTaxRate(): float;

    /**
     * Set tax rate
     *
     * @param float $taxRate
     * @return CreateOrderApiShipmentDetailsInterface
     */
    public function setTaxRate(float $taxRate): CreateOrderApiShipmentDetailsInterface;
}
