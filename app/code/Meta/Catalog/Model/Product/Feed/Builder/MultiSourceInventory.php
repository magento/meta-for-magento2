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
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Helper\FBEHelper;

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
     * @var GetStockItemConfigurationInterface
     */
    private GetStockItemConfigurationInterface $getStockItemConfiguration;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaInterfaceFactory;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param IsProductSalableInterface $isProductSalableInterface
     * @param GetProductSalableQtyInterface $getProductSalableQtyInterface
     * @param SystemConfig $systemConfig
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        IsProductSalableInterface          $isProductSalableInterface,
        GetProductSalableQtyInterface      $getProductSalableQtyInterface,
        SystemConfig                       $systemConfig,
        StockByWebsiteIdResolverInterface  $stockByWebsiteIdResolver,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        StockItemRepositoryInterface       $stockItemRepository,
        StockItemCriteriaInterfaceFactory  $stockItemCriteriaInterfaceFactory,
        FBEHelper                          $fbeHelper
    ) {
        $this->isProductSalableInterface = $isProductSalableInterface;
        $this->getProductSalableQtyInterface = $getProductSalableQtyInterface;
        $this->systemConfig = $systemConfig;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->fbeHelper = $fbeHelper;
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
            // Sampling rate of 1/1000 calls. Reasonable across millions of products
            if (random_int(1, 1000) <= 1) {
                $this->fbeHelper->logExceptionImmediatelytoMeta(
                    $e,
                    [
                        'store_id' => $this->product->getStoreId(),
                        'event' => 'catalog_sync',
                        'event_type' => 'multi_source_inventory_sync_is_in_stock_error',
                        'product_id' => $this->product->getSku(),
                        'stock_id' => $stockId
                    ]
                );
            }
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
            // Sampling rate of 1/1000 calls. Reasonable across millions of products
            if (random_int(1, 1000) <= 1) {
                $this->fbeHelper->logExceptionImmediatelytoMeta(
                    $e,
                    [
                        'store_id' => $this->product->getStoreId(),
                        'event' => 'catalog_sync',
                        'event_type' => 'multi_source_inventory_sync_get_stock_qty_error',
                        'product_id' => $this->product->getSku(),
                        'stock_id' => $stockId
                    ]
                );
            }
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
            $websiteId = (int)$this->product->getStore()->getWebsiteId();
            $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();
            $stockItemConfiguration = $this->getStockItemConfiguration->execute($this->product->getSku(), $stockId);
            return $stockItemConfiguration->isManageStock();
        } catch (\Throwable $e) {
            // Sampling rate of 1/1000 calls. Reasonable across millions of products
            if (random_int(1, 1000) <= 1) {
                $this->fbeHelper->logExceptionImmediatelytoMeta(
                    $e,
                    [
                        'store_id' => $this->product->getStoreId(),
                        'event' => 'catalog_sync',
                        'event_type' => 'multi_source_inventory_sync_is_stock_managed_error',
                        'product_id' => $this->product->getSku()
                    ]
                );
            }
            try {
                // fallback to single inventory mechanism in case of error
                $criteria = $this->stockItemCriteriaInterfaceFactory->create();
                $criteria->setProductsFilter($this->product->getId());
                $stocksItems = $this->stockItemRepository->getList($criteria)->getItems();
                $productStock = array_shift($stocksItems);
                return (bool)$productStock->getManageStock();
            } catch (\Throwable $e) {
                // if single inventory mechanism also fails, always return true and
                // let inventory count decide quantity to sell
                return true;
            }
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
