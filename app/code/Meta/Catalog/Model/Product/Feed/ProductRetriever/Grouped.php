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
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Framework\Exception\LocalizedException;
use Meta\Catalog\Model\ProductRepository;

class Grouped implements ProductRetrieverInterface
{
    private const LIMIT = 200;

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
     */
    public function __construct(
        FBEHelper $fbeHelper,
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepo = $productRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return ProductRetrieverInterface
     */
    public function setStoreId($storeId): ProductRetrieverInterface
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
        $storeId = $this->storeId ?? $this->fbeHelper->getStore()->getId();

        $configurableCollection = $this->productCollectionFactory->create();
        $configurableCollection->addAttributeToSelect('*')
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
            ->addAttributeToFilter('type_id', GroupedType::TYPE_CODE)
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        $configurableCollection->getSelect()->limit($limit, $offset);

        $search = $this
            ->searchCriteriaBuilder
            ->addFilter('entity_id', array_keys($configurableCollection->getItems()), 'in')
            ->create();

        $products = $this->productRepo->getList($search)->getItems();

        $simpleProducts = [];
        foreach ($products as $product) {
            /** @var Product $product */
            /** @var GroupedType $groupedType */
            $groupedType = $product->getTypeInstance();
            $groupedProduct = ['item_group_id' => $product->getId()];
            foreach ($groupedType->getAssociatedProducts($product) as $childProduct) {
                /** @var Product $childProduct */
                $childProduct->setConfigurableSettings($groupedProduct);
                $childProduct->setParentProductUrl($product->getProductUrl());
                $simpleProducts[] = $childProduct;
            }
        }

        return $simpleProducts;
    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return self::LIMIT;
    }
}
