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

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;

class Inventory extends InventoryRequirements implements InventoryInterface
{
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaInterfaceFactory;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var StockItemInterface
     */
    private $productStock;

    /**
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        StockItemRepositoryInterface      $stockItemRepository,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        SystemConfig                      $systemConfig
    ) {
        $this->stockItemRepository = $stockItemRepository;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Get stock item
     *
     * @param Product $product
     * @return StockItemInterface|null
     */
    public function getStockItem(Product $product): ?StockItemInterface
    {
        $criteria = $this->stockItemCriteriaInterfaceFactory->create();
        $criteria->setProductsFilter($product->getId());
        $stocksItems = $this->stockItemRepository->getList($criteria)->getItems();
        return array_shift($stocksItems);
    }

    /**
     * Init inventory for product
     *
     * @param Product $product
     * @return $this
     */
    public function initInventoryForProduct(Product $product): Inventory
    {
        $this->product = $product;
        $this->productStock = $this->getStockItem($product);
        return $this;
    }

    /**
     * Get product stock status
     *
     * @return string
     */
    public function getAvailability(): string
    {
        $inventory = $this->getInventory();

        // unmanaged stock is always available
        if ($inventory === self::UNMANAGED_STOCK_QTY) {
            return self::STATUS_IN_STOCK;
        }

        return $this->productStock && $this->productStock->getIsInStock()
        && $this->meetsInventoryRequirementsToBeInStock($this->product)
            ? self::STATUS_IN_STOCK : self::STATUS_OUT_OF_STOCK;
    }

    /**
     * Get available product qty
     *
     * @return int|float
     */
    public function getInventory()
    {
        if (!($this->product && $this->productStock)) {
            return 0;
        }

        if (!$this->productStock->getManageStock()) {
            return self::UNMANAGED_STOCK_QTY;
        }

        $outOfStockThreshold = $this->systemConfig->getOutOfStockThreshold($this->product->getStoreId());
        return max($this->productStock->getQty() - $outOfStockThreshold, 0);
    }
}
