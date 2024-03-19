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
use Meta\Sales\Api\Data\CreateOrderApiProductItemInterface;

class CreateOrderApiProductItem extends DataObject implements CreateOrderApiProductItemInterface
{

    /**
     * Retrieves the name of the product item.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_getData(self::DATA_NAME);
    }

    /**
     * Sets the name of the product item.
     *
     * @param string $name
     * @return CreateOrderApiProductItemInterface
     */
    public function setName(string $name): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_NAME, $name);
    }

    /**
     * Retrieves the SKU of the product item.
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->_getData(self::DATA_SKU);
    }

    /**
     * Sets the SKU of the product item.
     *
     * @param string $sku
     * @return CreateOrderApiProductItemInterface
     */
    public function setSku(string $sku): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_SKU, $sku);
    }

    /**
     * Retrieves the per unit base price of the product item.
     *
     * @return float
     */
    public function getPerUnitBasePrice(): float
    {
        return $this->_getData(self::DATA_PER_UNIT_BASE_PRICE);
    }

    /**
     * Sets the per unit base price of the product item.
     *
     * @param float $perUnitBasePrice
     * @return CreateOrderApiProductItemInterface
     */
    public function setPerUnitBasePrice(float $perUnitBasePrice): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_PER_UNIT_BASE_PRICE, $perUnitBasePrice);
    }

    /**
     * Retrieves the net price of the product item.
     *
     * @return float
     */
    public function getNetPrice(): float
    {
        return $this->_getData(self::DATA_NET_PRICE);
    }

    /**
     * Sets the net price of the product item.
     *
     * @param float $netPrice
     * @return CreateOrderApiProductItemInterface
     */
    public function setNetPrice(float $netPrice): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_NET_PRICE, $netPrice);
    }

    /**
     * Retrieves the subtotal price of the product item.
     *
     * @return float
     */
    public function getSubTotalPrice(): float
    {
        return $this->_getData(self::DATA_SUB_TOTAL_PRICE);
    }

    /**
     * Sets the subtotal price of the product item.
     *
     * @param float $subTotalPrice
     * @return CreateOrderApiProductItemInterface
     */
    public function setSubTotalPrice(float $subTotalPrice): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_SUB_TOTAL_PRICE, $subTotalPrice);
    }

    /**
     * Retrieves the tax amount of the product item.
     *
     * @return float
     */
    public function getTax(): float
    {
        return $this->_getData(self::DATA_TAX);
    }

    /**
     * Sets the tax amount of the product item.
     *
     * @param float $tax
     * @return CreateOrderApiProductItemInterface
     */
    public function setTax(float $tax): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_TAX, $tax);
    }

    /**
     * Retrieves the tax rate of the product item.
     *
     * @return float
     */
    public function getTaxRate(): float
    {
        return $this->_getData(self::DATA_TAX_RATE);
    }

    /**
     * Sets the tax rate of the product item.
     *
     * @param float $taxRate
     * @return CreateOrderApiProductItemInterface
     */
    public function setTaxRate(float $taxRate): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_TAX_RATE, $taxRate);
    }

    /**
     * Retrieves the quantity of the product item.
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->_getData(self::DATA_QUANTITY);
    }

    /**
     * Sets the quantity of the product item.
     *
     * @param int $quantity
     * @return CreateOrderApiProductItemInterface
     */
    public function setQuantity(int $quantity): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_QUANTITY, $quantity);
    }

    /**
     * Retrieves the discount applied to the product item.
     *
     * @return CreateOrderApiDiscountInterface | null
     */
    public function getDiscount(): ?CreateOrderApiDiscountInterface
    {
        return $this->_getData(self::DATA_DISCOUNT);
    }

    /**
     * Sets the discount applied to the product item.
     *
     * @param CreateOrderApiDiscountInterface $discount
     * @return CreateOrderApiProductItemInterface
     */
    public function setDiscount(CreateOrderApiDiscountInterface $discount): CreateOrderApiProductItemInterface
    {
        return $this->setData(self::DATA_DISCOUNT, $discount);
    }
}
