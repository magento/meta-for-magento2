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

namespace Meta\Catalog\Model\Category\CategoryUtility;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as Category;
use Magento\Catalog\Model\Category\Image as CategoryImageService;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Helper\Product\Identifier as ProductIdentifier;

class CategoryUtilities
{
    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollection;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var ProductIdentifier
     */
    private ProductIdentifier $productIdentifier;

    /**
     * @var CategoryImageService
     */
    private CategoryImageService $imageService;

    /**
     * Constructor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollection
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param ProductIdentifier $productIdentifier
     * @param CategoryImageService $imageService
     */
    public function __construct(
        ProductCollectionFactory    $productCollectionFactory,
        CategoryCollectionFactory   $categoryCollection,
        CategoryRepositoryInterface $categoryRepository,
        FBEHelper                   $fbeHelper,
        SystemConfig                $systemConfig,
        ProductIdentifier           $productIdentifier,
        CategoryImageService        $imageService
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->productIdentifier = $productIdentifier;
        $this->imageService = $imageService;
    }
    /**
     * Fetch products for product category
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
     *
     * @param Category $category
     * @param int $storeId
     * @return ProductCollection
     */
    public function getCategoryProducts(Category $category, $storeId): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStoreId($storeId);
        $productCollection->addAttributeToSelect('sku');
        $productCollection->distinct(true);
        $productCollection->addCategoriesFilter(['eq' => $category->getId()]);
        $productCollection->getSelect()->limit(10000);
        $this->fbeHelper->log("product collection count:" . $productCollection->getSize());

        return $productCollection;
    }

    /**
     * Create filter params for product set api
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
     *
     * @param ProductCollection $productCollection
     * @return string
     */
    public function getCategoryProductFilter(ProductCollection $productCollection): string
    {
        $productIdentifier = $this->productIdentifier->getProductIdentifierColName();
        if (!empty($productIdentifier)) {
            $ids = $productCollection->getColumnValues($productIdentifier);
        } else {
            $ids = [];
        }
        $filter = json_encode(['retailer_id' => ['is_any' => $ids]]);

        $this->fbeHelper->log("filter:" . $filter);

        return $filter;
    }

    /**
     * Return Category logger context for be logged
     *
     * @param int $storeId
     * @param string $eventType
     * @param array $extraData
     * @return array
     */
    public function getCategoryLoggerContext($storeId, $eventType, $extraData): array
    {
        return [
            'store_id' => $storeId,
            'event' => 'category_sync',
            'event_type' => $eventType,
            'catalog_id' => $this->systemConfig->getCatalogId($storeId),
            'extra_data' => $extraData
        ];
    }

    /**
     * Get all active categories
     *
     * @param int $storeId
     * @return Collection
     * @throws \Throwable
     */
    public function getAllActiveCategories($storeId): Collection
    {
        $store = $this->systemConfig->getStoreManager()->getStore($storeId);
        $rootCategoryId = $store->getRootCategoryId();
        $rootCategory = $this->categoryRepository->get($rootCategoryId, $storeId);

        return $this->getAllChildrenCategories($rootCategory, $storeId, true);
    }

    /**
     * Get all children categories
     *
     * Get all children node in category tree recursion is being used.
     *
     * @param Category $category
     * @param int $storeId
     * @param bool $onlyActiveCategories
     * @return Collection
     * @throws \Throwable
     */
    public function getAllChildrenCategories(
        Category $category,
        $storeId,
        bool $onlyActiveCategories = false
    ): Collection {
        $this->fbeHelper->log(sprintf(
            "searching%s children category for category: %s",
            $onlyActiveCategories ? ' active' : '',
            $category->getName()
        ));
        $categoryPath = $category->getPath();

        $categories = $this->categoryCollection->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(
                [
                    [
                        "attribute" => "path",
                        "like" => $categoryPath . "/%"
                    ],
                    [
                        "attribute" => "path",
                        "like" => $categoryPath
                    ]
                ]
            );

        if ($onlyActiveCategories) {
            return $categories->addAttributeToFilter('is_active', 1);
        }
        return $categories;
    }

    /**
     * Save key with a fb product set ID
     *
     * @param Category $category
     * @param string $setId
     * @param int $storeId
     * @throws \Throwable
     */
    public function saveFBProductSetID(Category $category, string $setId, $storeId): void
    {
        $this->fbeHelper->log(sprintf(
            "saving category %s ,id %s ,storeId %s and setId %s",
            $category->getName(),
            $category->getId(),
            $storeId,
            $setId
        ));

        $category->setData(SystemConfig::META_PRODUCT_SET_ID, $setId);
        $this->saveCategoryForStore($category, $storeId);
    }

    /**
     * Save category for store
     *
     * @param Category $category
     * @param int $storeId
     */
    private function saveCategoryForStore(Category $category, $storeId): void
    {
        try {
            if (null !== $storeId) {
                $currentStoreId = $this->systemConfig->getStoreManager()->getStore()->getId();
                // needs to update it as category save function using storeId from store Manager
                $this->systemConfig->getStoreManager()->setCurrentStore($storeId);
                $this->categoryRepository->save($category);
                $this->systemConfig->getStoreManager()->setCurrentStore($currentStoreId);
                return;
            }

            $this->categoryRepository->save($category);
        } catch (\Throwable $e) {
            $this->fbeHelper->logException($e);
        }
    }

    /**
     * Get category landing page URL.
     *
     * It first tries request path and URL finder for category, if fails to fetch it return category canonical URL
     *
     * @param Category $category
     * @return string
     */
    private function getDirectCategoryURL(Category $category): string
    {
        try {
            // fetch url from getURL function
            $url = $category->getUrl();

            // if url returned by getURL is category admin page url, replace it with category canonical URL
            if (strpos($url, 'admin/catalog/category/view/s') !== false) {
                // if nothing works return URL by category ID (canonical URL)
                $urlKey = $category->getUrlKey() ?? $category->formatUrlKey($category->getName());

                $url = $category->getUrlInstance()->getDirectUrl(
                    sprintf('catalog/category/view/s/%s/id/%s/', $urlKey, $category->getId())
                );

                $this->fbeHelper->log(sprintf(
                    "Category canonical URL used for category: %s, url: %s",
                    $category->getName(),
                    $url
                ));
                return $url;
            }

            $this->fbeHelper->log(sprintf(
                "Category getURL function used for category: %s, url: %s",
                $category->getName(),
                $url
            ));
            return $url;
        } catch (\Throwable $e) {
            $this->fbeHelper->logException($e);
            return '';
        }
    }

    /**
     * Get category MetaData for FB product set
     *
     * @param Category $category
     * @return array|null
     */
    public function getCategoryMetaData(Category $category): ?array
    {
        try {
            $categoryImageURL = $this->imageService->getUrl($category);
        } catch (\Throwable $e) {
            $categoryImageURL = null;
            $this->fbeHelper->logException($e);
        }

        $categoryURL = $this->getDirectCategoryURL($category);

        // check if url path exist use it, otherwise url_key
        $categoryURLHandle = empty($category->getData('url_path'))
            ? $category->getUrlKey() : $category->getData('url_path');

        return [
            'cover_image_url' => $categoryImageURL,
            'external_url' => $categoryURL,
            'external_url_handle' => $categoryURLHandle
        ];
    }

    /**
     * Get category path name
     *
     * If the category is Tops we might create "Default Category > Men > Tops"
     *
     * @param Category $category
     * @param int $storeId
     * @return string
     */
    public function getCategoryPathName(Category $category, $storeId): string
    {
        $categoryPath = (string)$category->getPath();

        return implode(" > ", array_filter(array_map(
            function ($innerId) use ($storeId) {
                try {
                    $innerCategory = $this->categoryRepository->get($innerId, $storeId);
                    // parent of root category
                    if ($innerCategory->getLevel() == 0) {
                        return null;
                    }
                    return $innerCategory->getName();
                } catch (\Throwable $e) {
                    return null;
                }
            },
            explode("/", $categoryPath)
        )));
    }
}
