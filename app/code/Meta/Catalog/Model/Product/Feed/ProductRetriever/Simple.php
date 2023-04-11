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
namespace Meta\Catalog\Model\Product\Feed\ProductRetriever;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Simple implements ProductRetrieverInterface
{
    private const LIMIT = 2000;

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
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(FBEHelper $fbeHelper, CollectionFactory $productCollectionFactory)
    {
        $this->fbeHelper = $fbeHelper;
        $this->productCollectionFactory = $productCollectionFactory;
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
     * @inheritDoc
     */
    public function getProductType()
    {
        return ProductType::TYPE_SIMPLE;
    }

    /**
     * Retrieve products
     *
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function retrieve($offset = 1, $limit = self::LIMIT): array
    {
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
            ->addAttributeToFilter('type_id', $this->getProductType())
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        $collection
            ->getSelect()
            ->joinLeft(['l' => $collection->getTable('catalog_product_super_link')], 'e.entity_id = l.product_id')
            ->where('l.product_id IS NULL')
            ->order(new \Zend_Db_Expr('e.updated_at desc'))
            ->limit($limit, $offset);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return self::LIMIT;
    }
}
