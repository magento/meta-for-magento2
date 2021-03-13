<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Product\Feed\ProductRetriever;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Simple implements ProductRetrieverInterface
{
    const LIMIT = 2000;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

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
    public function getProductType()
    {
        return ProductType::TYPE_SIMPLE;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function retrieve($offset = 1, $limit = self::LIMIT): array
    {
        $storeId = $this->fbeHelper->getStore()->getId();

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter('type_id', $this->getProductType())
            ->setStoreId($storeId);

        $collection
            ->getSelect()->joinLeft(['l' => 'catalog_product_super_link'], 'e.entity_id = l.product_id')
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
