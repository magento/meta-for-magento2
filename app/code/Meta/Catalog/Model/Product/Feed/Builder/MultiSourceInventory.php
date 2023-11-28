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

namespace Meta\Catalog\Model\Product\Feed\Builder;

use Magento\Catalog\Model\Product;
use Magento\InventorySalesAdminUi\Model\GetIsManageStockForProduct;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class MultiSourceInventory extends InventoryRequirements implements InventoryInterface
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
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var bool
     */
    private $stockStatus;

    /**
     * @var int|float
     */
    private $stockQty;

    /**
     * @var GetIsManageStockForProduct
     */
    private GetIsManageStockForProduct $getIsManageStockForProduct;

    /**
     * @param IsProductSalableInterface $isProductSalableInterface
     * @param GetProductSalableQtyInterface $getProductSalableQtyInterface
     * @param SystemConfig $systemConfig
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetIsManageStockForProduct $getIsManageStockForProduct
     */
    public function __construct(
        IsProductSalableInterface         $isProductSalableInterface,
        GetProductSalableQtyInterface     $getProductSalableQtyInterface,
        SystemConfig                      $systemConfig,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        GetIsManageStockForProduct        $getIsManageStockForProduct
    ) {
        $this->isProductSalableInterface = $isProductSalableInterface;
        $this->getProductSalableQtyInterface = $getProductSalableQtyInterface;
        $this->systemConfig = $systemConfig;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getIsManageStockForProduct = $getIsManageStockForProduct;
    }

    /**
     * Fetch the stock status for product
     *
     * @param Product $product
     * @param int $stockId
     * @return bool
     */
    private function isInStock(Product $product, int $stockId): bool
    {
        try {
            return $this->isProductSalableInterface->execute(
                $product->getSku(),
                $stockId
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch stock quantity for product
     *
     * @param Product $product
     * @param int $stockId
     * @return int|float
     */
    private function getStockQty(Product $product, int $stockId)
    {
        try {
            return $this->getProductSalableQtyInterface->execute(
                $product->getSku(),
                $stockId
            );
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Checks if product is having managed stock
     *
     * @return bool
     */
    public function isStockManagedForProduct(): bool
    {
        try {
            $websiteCode = $this->product->getStore()->getWebsite()->getCode();
            return $this->getIsManageStockForProduct->execute($this->product->getSku(), $websiteCode);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Initiate inventory for the product
     *
     * @param Product $product
     * @return $this
     */
    public function initInventoryForProduct(Product $product): MultiSourceInventory
    {
        $websiteId = (int)$product->getStore()->getWebsiteId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();
        $this->product = $product;
        $this->stockStatus = $this->isInStock($product, $stockId);
        $this->stockQty = $this->getStockQty($product, $stockId);
        return $this;
    }

    /**
     * Get product stock status
     *
     * @return string
     */
    public function getAvailability(): string
    {
        // unmanaged stock is always available
        if (!$this->isStockManagedForProduct()) {
            return self::STATUS_IN_STOCK;
        }

        return $this->meetsInventoryRequirementsToBeInStock($this->product)
        && $this->stockStatus ? self::STATUS_IN_STOCK : self::STATUS_OUT_OF_STOCK;
    }

    /**
     * Get available product qty
     *
     * @return int
     */
    public function getInventory(): int
    {
        if (!$this->product) {
            return 0;
        }

        if (!$this->isStockManagedForProduct()) {
            return self::UNMANAGED_STOCK_QTY;
        }

        $outOfStockThreshold = $this->systemConfig->getOutOfStockThreshold($this->product->getStoreId());
        $quantityAvailableForCatalog = (int)$this->stockQty - $outOfStockThreshold;
        return $quantityAvailableForCatalog > 0 ? $quantityAvailableForCatalog : 0;
    }
}
