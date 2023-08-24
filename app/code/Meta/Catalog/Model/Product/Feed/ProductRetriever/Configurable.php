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

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use Meta\Catalog\Model\ProductRepository;

class Configurable implements ProductRetrieverInterface
{
    private const LIMIT = 200;

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
     * @var ProductRepository
     */
    private $metaProductRepo;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepository $metaProductRepo
     */
    public function __construct(
        FBEHelper $fbeHelper,
        CollectionFactory $productCollectionFactory,
        ProductRepository $metaProductRepo
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->metaProductRepo = $metaProductRepo;
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
     */
    public function getProductType()
    {
        return ConfigurableType::TYPE_CODE;
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
            ->addAttributeToFilter('type_id', $this->getProductType())
            ->addStoreFilter($storeId)
            ->setStoreId($storeId)
            ->addMediaGalleryData();

        $configurableCollection->getSelect()->limit($limit, $offset);

        $simpleProducts = [];

        foreach ($configurableCollection as $product) {
            /** @var Product $product */
            /** @var ConfigurableType $configurableType */
            $configurableType = $product->getTypeInstance();
            foreach ($configurableType->getUsedProducts($product) as $childProduct) {
                $simpleProducts[] = $this->metaProductRepo->loadParentProductData($childProduct, $product);
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
