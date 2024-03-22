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
 * Model for the product item payload sent to createOrderApi
 */
interface CreateOrderApiProductItemInterface
{
    public const DATA_NAME = "name";
    public const DATA_SKU = "sku";
    public const DATA_PER_UNIT_BASE_PRICE = "perUnitBasePrice";
    public const DATA_NET_PRICE = "netPrice";
    public const DATA_SUB_TOTAL_PRICE = "subTotalPrice";
    public const DATA_TAX = "tax";
    public const DATA_TAX_RATE = "taxRate";
    public const DATA_QUANTITY = "quantity";
    public const DATA_DISCOUNT = "discount";

    /**
     * Get the product name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set product name
     *
     * @param string $name
     * @return CreateOrderApiProductItemInterface
     */
    public function setName(string $name): CreateOrderApiProductItemInterface;

    /**
     * Get product SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Get product SKU
     *
     * @param string $sku
     * @return CreateOrderApiProductItemInterface
     */
    public function setSku(string $sku): CreateOrderApiProductItemInterface;

    /**
     * Get product unit base price
     *
     * @return float
     */
    public function getPerUnitBasePrice(): float;

    /**
     * Set product unit base price
     *
     * @param float $perUnitBasePrice
     * @return CreateOrderApiProductItemInterface
     */
    public function setPerUnitBasePrice(float $perUnitBasePrice): CreateOrderApiProductItemInterface;

    /**
     * Get product net price = subtotal + tax
     *
     * @return float
     */
    public function getNetPrice(): float;

    /**
     * Set product net price
     *
     * @param float $netPrice
     * @return CreateOrderApiProductItemInterface
     */
    public function setNetPrice(float $netPrice): CreateOrderApiProductItemInterface;

    /**
     * Get product subtotal = unit price * quantity
     *
     * @return float
     */
    public function getSubTotalPrice(): float;

    /**
     * Set product subtotal
     *
     * @param float $subTotalPrice
     * @return CreateOrderApiProductItemInterface
     */
    public function setSubTotalPrice(float $subTotalPrice): CreateOrderApiProductItemInterface;

    /**
     * Get product tax
     *
     * @return float
     */
    public function getTax(): float;

    /**
     * Set product tax
     *
     * @param float $tax
     * @return CreateOrderApiProductItemInterface
     */
    public function setTax(float $tax): CreateOrderApiProductItemInterface;

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
     * @return CreateOrderApiProductItemInterface
     */
    public function setTaxRate(float $taxRate): CreateOrderApiProductItemInterface;

    /**
     * Get product quantity
     *
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Set product quantity
     *
     * @param int $quantity
     * @return CreateOrderApiProductItemInterface
     */
    public function setQuantity(int $quantity): CreateOrderApiProductItemInterface;

    /**
     * Get discount
     *
     * @return \Meta\Sales\Api\Data\CreateOrderApiDiscountInterface | null
     */
    public function getDiscount(): ?CreateOrderApiDiscountInterface;

    /**
     * Set discount
     *
     * @param \Meta\Sales\Api\Data\CreateOrderApiDiscountInterface $discount
     * @return CreateOrderApiProductItemInterface
     */
    public function setDiscount(CreateOrderApiDiscountInterface $discount): CreateOrderApiProductItemInterface;
}
