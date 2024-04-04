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

namespace Meta\Catalog\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\App\ResourceConnection;
use Meta\BusinessExtension\Helper\FBEHelper;

class ProductRepository
{
    /**
     * @var Configurable
     */
    private $configurableProductType;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var OptionProvider
     */
    private $optionProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ProductRepository constructor
     *
     * @param Configurable $configurableProductType
     * @param CollectionFactory $collectionFactory
     * @param OptionProvider $optionProvider
     * @param ResourceConnection $resourceConnection
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        Configurable       $configurableProductType,
        CollectionFactory  $collectionFactory,
        OptionProvider     $optionProvider,
        ResourceConnection $resourceConnection,
        FBEHelper          $fbeHelper
    ) {
        $this->configurableProductType = $configurableProductType;
        $this->collectionFactory = $collectionFactory;
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Load product with parent data for simple products
     *
     * @param Product $childProduct
     * @param Product $product
     * @return Product
     */
    public function loadParentProductData(Product $childProduct, Product $product): Product
    {
        $configurableAttributes = $this->configurableProductType->getConfigurableAttributes($product);
        $configurableSettings = ['item_group_id' => $product->getId()];
        foreach ($configurableAttributes as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeCode = $productAttribute->getAttributeCode();
            $attributeValue = $childProduct->getData($productAttribute->getAttributeCode());
            $attributeLabel = $productAttribute->getSource()->getOptionText($attributeValue);
            $configurableSettings[$attributeCode] = $attributeLabel;
        }
        // Assign parent product name to all child products' name (used as variant name is Meta catalog)
        // https://developers.facebook.com/docs/commerce-platform/catalog/variants
        $childProduct->setName($product->getName());
        $childProduct->setConfigurableSettings($configurableSettings);
        $childProduct->setParentProductUrl($product->getProductUrl());
        $childProduct->setVisibility($product->getVisibility());
        //todo put all these attributes to a list
        if (!$childProduct->getDescription()) {
            $childProduct->setDescription($product->getDescription());
        }
        if (!$childProduct->getShortDescription()) {
            $childProduct->setShortDescription($product->getShortDescription());
        }
        if (!$childProduct->getWeight()) {
            $childProduct->setWeight($product->getWeight());
        }

        $material = $childProduct->getResource()->getAttribute('material');
        if ($material && !$material->getSource()->getOptionText($childProduct->getData('material'))) {
            $childProduct->setData('material', $product->getData('material'));
        }

        $pattern = $childProduct->getResource()->getAttribute('pattern');
        if ($pattern && !$pattern->getSource()->getOptionText($childProduct->getData('pattern'))) {
            $childProduct->setData('pattern', $product->getData('pattern'));
        }
        $this->appendImagesFromParentProduct($childProduct, $product);
        return $childProduct;
    }

    /**
     * We append parent images to variants, mimicking the magento behaviour.
     *
     * @param Product $childProduct
     * @param Product $product
     * @return void
     */
    private function appendImagesFromParentProduct(Product $childProduct, Product $product)
    {
        // If no main image in childProduct, use parent product image
        if (!$childProduct->getData('image') || $childProduct->getData('image') === 'no_selection') {
            $childProduct->setData('image', $product->getImage());
        }

        // Append media from parent product to child product
        $child_product_additional_images = $childProduct->getMediaGalleryImages();
        $parent_product_additional_images = $product->getMediaGalleryImages();
        foreach ($parent_product_additional_images as $parent_product_additional_image) {
            try {
                $child_product_additional_images->addItem($parent_product_additional_image);
            } catch (\Exception $exception) {
                $this->fbeHelper->logException($exception);
            }
        }
        $childProduct->setData('media_gallery_images', $child_product_additional_images);
    }

    /**
     * Get collection of products from productUpdates
     *
     * @param array $productIds
     * @param int $storeId
     * @return Product[]
     */
    public function getCollection(array $productIds, $storeId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $productIds])
            ->addStoreFilter($storeId)
            ->setStoreId($storeId)
            ->getSelect();

        return $collection->getItems();
    }

    /**
     * Get collection of parent products based on childIds
     *
     * @param array $childProductIds
     * @param int $storeId
     * @return ProductCollection
     */
    public function getParentProducts(array $childProductIds, $storeId)
    {
        $parentIdentifier = $this->optionProvider->getProductEntityLinkField();
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addStoreFilter($storeId)
            ->setStoreId($storeId)
            ->getSelect()
            ->joinInner(
                ['l' => $collection->getTable('catalog_product_super_link')],
                "e.{$parentIdentifier} = l.parent_id"
            )
            ->where('l.product_id IN (?)', $childProductIds)
            ->group('l.parent_id');

        return $collection;
    }

    /**
     * Get Parent product links
     *
     * @param array $productIds
     * @return ProductCollection
     */
    public function getParentProductLink(array $productIds)
    {
        $parentIdentifier = $this->optionProvider->getProductEntityLinkField();
        $connection = $this->resourceConnection->getConnection();
        $linkTable = $this->resourceConnection->getTableName('catalog_product_super_link');
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $select = $connection->select()->from($linkTable, 'product_id')
            ->joinInner(
                ['p' => $productTable],
                "parent_id = p.{$parentIdentifier}",
                'p.entity_id'
            )
            ->where('product_id IN (?)', $productIds);

        return $connection->fetchPairs($select);
    }
}
