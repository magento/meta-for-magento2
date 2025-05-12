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

namespace Meta\Conversion\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;

/**
 * Helper class to get data using Magento Platform methods.
 *
 */
class MagentoDataHelper
{
    private const PRODUCT_IDENTIFIER_SKU = 'sku';
    private const PRODUCT_IDENTIFIER_ID = 'id';

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * @var string
     */
    private $identifierAttr;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $categoryCollection;

    /**
     * MagentoDataHelper constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param PricingHelper $pricingHelper
     * @param SystemConfig $systemConfig
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        PricingHelper $pricingHelper,
        SystemConfig $systemConfig,
        CollectionFactory $categoryCollection
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pricingHelper = $pricingHelper;
        $this->identifierAttr = $systemConfig->getProductIdentifierAttr();
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * Return the product by the given sku
     *
     * @param string $productSku
     * @return ProductInterface | bool
     */
    public function getProductBySku(string $productSku)
    {
        try {
            return $this->productRepository->get($productSku);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Get Product by ID
     *
     * @param mixed $productId
     * @return false|ProductInterface
     */
    public function getProductById($productId)
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Return the categories for the given product
     *
     * @param Product $product
     * @return string
     */
    public function getCategoriesForProduct(Product $product): string
    {
        $categoryIds = $product->getCategoryIds();
        if (count($categoryIds) > 0) {
            $categoryNames = [];
            foreach ($categoryIds as $categoryId) {
                try {
                    $category = $this->categoryRepository->get($categoryId);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
                $categoryNames[] = $category->getName();
            }
            return addslashes(implode(',', $categoryNames)); // phpcs:ignore
        }

        return '';
    }

    /**
     * Get Categories Names by ids
     *
     * @param array $categoryIds
     * @return string
     */
    public function getCategoriesNameById(array $categoryIds): string
    {
        if (count($categoryIds) > 0) {
            $categoryNames = [];
            $collection = $this->categoryCollection->create()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', $categoryIds);
            foreach ($collection as $category) {
                $categoryNames[] = $category->getName();
            }
            return addslashes(implode(',', $categoryNames)); // phpcs:ignore
        }
        return '';
    }

    /**
     * Get content type
     *
     * @param Product $product
     * @return string
     */
    public function getContentType(Product $product): string
    {
        $productType = '';
        
        switch ($product->getTypeId()) {
            case BundleType::TYPE_CODE:
            case Configurable::TYPE_CODE:
            case GroupedType::TYPE_CODE:
                $productType = 'product_group';
                break;
            default:
                $productType = 'product';
                break;
        }

        return $productType;
    }

    /**
     * Get Content IDs (Product IDs)
     *
     * @param Product $product
     * @return bool|int|string
     */
    public function getContentId(Product $product)
    {
        if ($this->identifierAttr === self::PRODUCT_IDENTIFIER_SKU) {
            return $product->getSku();
        } elseif ($this->identifierAttr === self::PRODUCT_IDENTIFIER_ID) {
            return $product->getId();
        }
        return false;
    }

    /**
     * Return the price for the given product
     *
     * @param Product $product
     * @return float
     */
    public function getValueForProduct(Product $product): float
    {
        $price = $product->getFinalPrice();
        return $this->pricingHelper->currency($price, false, false);
    }

    /**
     * Return the currency used in the store
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Return the cart total value
     *
     * @param CartInterface $quote
     * @return float|null
     */
    public function getCartTotal(CartInterface $quote): ?float
    {
        if (!$quote) {
            return null;
        }
        $subtotal = $quote->getSubtotal();
        if ($subtotal) {
            return $this->pricingHelper->currency($subtotal, false, false);
        } else {
            return null;
        }
    }

    /**
     * Return the amount of items in the cart
     *
     * @param CartInterface $quote
     * @return int
     */
    public function getCartNumItems(CartInterface $quote): int
    {
        $numItems = 0;
        if (!$quote) {
            return $numItems;
        }
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $numItems += $item->getQty();
        }
        return (int) $numItems;
    }

    /**
     * Get Hash value
     *
     * @param string|null $string
     * @return string
     */
    public function hashValue($string): string
    {
        return hash('sha256', strtolower($string ?? ""));
    }

    // TODO Remaining user/custom data methods that can be obtained using Magento.
}
