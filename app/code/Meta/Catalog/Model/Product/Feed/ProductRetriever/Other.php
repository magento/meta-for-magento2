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

namespace Meta\Catalog\Model\Product\Feed\ProductRetriever;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Catalog\Model\Product\Type as SimpleType;
use Magento\Framework\Exception\LocalizedException;

class Other implements ProductRetrieverInterface
{
    private const LIMIT = 20;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepo;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepo
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $fbeHelper,
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepo = $productRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return ProductRetrieverInterface|void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function retrieve($offset = 1, $limit = self::LIMIT): array
    {
        if ($this->systemConfig->isUnsupportedProductsDisabled()) {
            return [];
        }

        $storeId = $this->storeId ?? $this->fbeHelper->getStore()->getId();

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter([
                [
                    'attribute' => 'send_to_facebook',
                    'neq' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO
                ],
                [
                    'attribute' => 'send_to_facebook',
                    'null' => true
                ]
            ], null, 'left')
            ->addAttributeToFilter([
                [
                    'attribute' => 'type_id',
                    'neq' => ConfigurableType::TYPE_CODE
                ]
            ], null, 'left')
            ->addAttributeToFilter([
                [
                    'attribute' => 'type_id',
                    'neq' => SimpleType::TYPE_SIMPLE
                ],
            ], null, 'left')
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        $collection
            ->getSelect()
            ->joinLeft(['l' => $collection->getTable('catalog_product_super_link')], 'e.entity_id = l.product_id')
            ->where('l.product_id IS NULL')
            ->order(new \Zend_Db_Expr('e.updated_at desc'))
            ->limit($limit, $offset);

        $search = $this
            ->searchCriteriaBuilder
            ->addFilter('entity_id', array_keys($collection->getItems()), 'in')
            ->addFilter('store_id', $storeId)
            ->create();

        return $this->productRepo->getList($search)->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return self::LIMIT;
    }
}
