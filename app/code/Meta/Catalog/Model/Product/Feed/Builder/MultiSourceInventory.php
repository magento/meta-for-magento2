<?php
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

namespace Meta\Catalog\Model\Product\Feed\Builder;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

class MultiSourceInventory implements InventoryInterface
{
    /**
     * @var Product
     */
    private $product;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalableInterface;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQtyInterface;

    /**
     * @var bool
     */
    protected $stockStatus = false;

    /**
     * @var int|float
     */
    protected $stockQty = 0;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @param IsProductSalableInterface $isProductSalableInterface
     * @param GetProductSalableQtyInterface $getProductSalableQtyInterface
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        IsProductSalableInterface $isProductSalableInterface,
        GetProductSalableQtyInterface $getProductSalableQtyInterface,
        SystemConfig $systemConfig
    ) {
        $this->isProductSalableInterface = $isProductSalableInterface;
        $this->getProductSalableQtyInterface = $getProductSalableQtyInterface;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function getStockStatus(Product $product)
    {
        try {
            return $this->isProductSalableInterface->execute(
                $this->product->getSku(),
                $this->systemConfig->getInventoryStock($product->getStoreId())
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param Product $product
     * @return float|int
     */
    public function getStockQty(Product $product)
    {
        try {
            return $this->getProductSalableQtyInterface->execute(
                $this->product->getSku(),
                $this->systemConfig->getInventoryStock($product->getStoreId())
            );
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function initInventoryForProduct(Product $product)
    {
        $this->product = $product;
        $this->stockStatus = $this->getStockStatus($product);
        $this->stockQty = $this->getStockQty($product);
        return $this;
    }

    /**
     * Get product stock status
     *
     * @return string
     */
    public function getAvailability()
    {
        return $this->getInventory() && $this->stockStatus ? self::STATUS_IN_STOCK : self::STATUS_OUT_OF_STOCK;
    }

    /**
     * Get available product qty
     *
     * @return int
     */
    public function getInventory()
    {
        if (!$this->product) {
            return 0;
        }

        $outOfStockThreshold = $this->systemConfig->getOutOfStockThreshold($this->product->getStoreId());
        return (int)max($this->stockQty - $outOfStockThreshold, 0);
    }
}
