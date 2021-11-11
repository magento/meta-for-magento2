<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Inventory\Model\Stock;

class InventoryStock extends AbstractSource
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ModuleManager $moduleManager
     */
    protected $moduleManager;

    /**
     * InventoryStock constructor
     *
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param ObjectManagerInterface $objectManager
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ObjectManagerInterface $objectManager,
        ModuleManager $moduleManager
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return bool|mixed
     */
    protected function getStockRepository()
    {
        if (!$this->moduleManager->isEnabled('Magento_InventoryApi')) {
            return false;
        }

        try {
            return $this->objectManager->get('Magento\InventoryApi\Api\StockRepositoryInterface');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        $stockRepository = $this->getStockRepository();
        if (!$stockRepository) {
            return [];
        }

        if ($this->_options === null) {
            /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder->create();
            $stocks = $stockRepository->getList($searchCriteria)->getItems();
            foreach ($stocks as $stock) {
                /** @var Stock $stock */
                $this->_options[] = [
                    'value' => $stock->getStockId(),
                    'label' => $stock->getName()
                ];
            }
        }
        return $this->_options;
    }
}
