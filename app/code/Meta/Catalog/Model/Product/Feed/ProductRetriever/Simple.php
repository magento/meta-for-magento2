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
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Simple implements ProductRetrieverInterface
{
    private const LIMIT_DEFAULT = 200;

    /**
     * @var int
     */
    private $limit;

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
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepo
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper                  $fbeHelper,
        CollectionFactory          $productCollectionFactory,
        ProductRepositoryInterface $productRepo,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        SystemConfig               $systemConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepo = $productRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->systemConfig = $systemConfig;
        $this->limit = $this->systemConfig->getProductsFetchBatchSize(self::LIMIT_DEFAULT);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Retrieve products
     *
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function retrieve($offset = 1, $limit = null): array
    {
        if ($limit == null) {
            $limit = $this->getLimit();
        }
        $storeId = $this->storeId ?? $this->fbeHelper->getStore()->getId();

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
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
            ->addAttributeToFilter('type_id', ProductType::TYPE_SIMPLE)
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        $collection
            ->getSelect()
            ->joinLeft(['l' => $collection->getTable('catalog_product_super_link')], 'e.entity_id = l.product_id')
            ->where('l.product_id IS NULL')
            ->order(new \Zend_Db_Expr('e.updated_at desc'))
            ->limit($limit, $offset);

        if ($this->systemConfig->isAdditionalAttributesSyncDisabled()) {
            $products = $collection->getItems();
        } else {
            // in case of unsupported product we need complete data for products which is return by product repo api.
            $search = $this
                ->searchCriteriaBuilder
                ->addFilter('entity_id', array_keys($collection->getItems()), 'in')
                ->create();

            $products = $this->productRepo->getList($search)->getItems();
        }
        return $products;
    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
