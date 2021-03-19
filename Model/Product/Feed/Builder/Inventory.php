<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Product\Feed\Builder;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;

class Inventory
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
     * @var StockConfigurationInterface
     */
    private $stockConfigurationInterface;

    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;

    /**
     * @var mixed
     */
    protected $sourceCode;

    /**
     * @var SourceItemInterface|null
     */
    protected $sourceItem;

    /**
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param StockConfigurationInterface $stockConfigurationInterface
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        StockConfigurationInterface $stockConfigurationInterface,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        SystemConfig $systemConfig
    ) {
        $this->stockItemRepository = $stockItemRepository;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->stockConfigurationInterface = $stockConfigurationInterface;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceCode = $systemConfig->getInventorySource();
    }

    /**
     * @deprecated Replaced with multi-source inventory
     * @param Product $product
     * @return StockItemInterface|null
     */
    public function getStockItem(Product $product)
    {
        $criteria = $this->stockItemCriteriaInterfaceFactory->create();
        $criteria->setProductsFilter($product->getId());
        $stocksItems = $this->stockItemRepository->getList($criteria)->getItems();
        return array_shift($stocksItems);
    }

    /**
     * @param Product $product
     * @return SourceItemInterface|null
     */
    public function getSourceItem(Product $product)
    {
        try {
            return $this->getSourceItemBySourceCodeAndSku->execute($this->sourceCode, $product->getSku());
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
