<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Product\Feed\ProductRetriever;

use Facebook\BusinessExtension\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;

class Configurable implements ProductRetrieverInterface
{
    const LIMIT = 200;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(CollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function getProductType()
    {
        return ConfigurableType::TYPE_CODE;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function retrieve($offset = 1, $limit = self::LIMIT): array
    {
        $configurableCollection = $this->productCollectionFactory->create();
        $configurableCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter('type_id', $this->getProductType());

        $configurableCollection->getSelect()->limit($limit, $offset);

        $simpleProducts = [];

        foreach ($configurableCollection as $product) {
            /** @var ConfigurableType $configurableType */
            $configurableType = $product->getTypeInstance();
            $configurableAttributes = $configurableType->getConfigurableAttributes($product);

            foreach ($configurableType->getUsedProducts($product) as $childProduct) {
                /** @var Product $childProduct */
                $configurableSettings = ['item_group_id' => $product->getSku()];
                foreach ($configurableAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $attributeCode = $productAttribute->getAttributeCode();
                    $attributeValue = $childProduct->getData($productAttribute->getAttributeCode());
                    $attributeLabel = $productAttribute->getSource()->getOptionText($attributeValue);
                    $configurableSettings[$attributeCode] = $attributeLabel;
                }
                $childProduct->setConfigurableSettings($configurableSettings);
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
