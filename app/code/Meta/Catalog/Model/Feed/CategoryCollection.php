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

namespace Meta\Catalog\Model\Feed;

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

class CategoryCollection
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollection;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductIdentifier
     */
    private ProductIdentifier $productIdentifier;

    /**
     * @var CategoryImageService
     */
    private $imageService;

    private const BATCH_MAX = 49;

    /**
     * Constructor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollection
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     * @param ProductIdentifier $productIdentifier
     * @param CategoryImageService $imageService
     */
    public function __construct(
        ProductCollectionFactory    $productCollectionFactory,
        CategoryCollectionFactory   $categoryCollection,
        CategoryRepositoryInterface $categoryRepository,
        FBEHelper                   $helper,
        SystemConfig                $systemConfig,
        ProductIdentifier           $productIdentifier,
        CategoryImageService        $imageService
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
        $this->productIdentifier = $productIdentifier;
        $this->imageService = $imageService;
    }

    /**
     * Makes HTTP request after category save
     *
     * Get called after user save category, if it is new leaf category, we will create new collection on fb side,
     * if it is changing existed category, we just update the corresponding fb collection.
     *
     * @param Category $category
     * @param bool $isNameChanged
     * @return void
     * @throws \Throwable
     */
    public function makeHttpRequestsAfterCategorySave(Category $category, bool $isNameChanged): void
    {
        $storeIds = $category->getStoreIds();
        $this->fbeHelper->log(
            "Category real time update: store counts: " . count($storeIds)
        );

        foreach ($storeIds as $storeId) {
            try {
                $categories = [];
                if ($isNameChanged) {
                    $categories = $this->getAllActiveChildrenCategories($category, $storeId);
                } else {
                    $categories[] = $this->categoryRepository->get($category->getId(), $storeId);
                }

                if (!$this->systemConfig->isCatalogSyncEnabled($storeId)) {
                    $this->fbeHelper->log(
                        "Category real time update: meta catalog sync is not enabled for store: " . $storeId
                    );
                    continue;
                }

                $accessToken = $this->systemConfig->getAccessToken($storeId);
                if ($accessToken === null) {
                    $this->fbeHelper->log(
                        "can't find access token, won't update category with fb, storeId: " . $storeId
                    );
                    continue;
                }

                $this->pushCategoriesToFBCollections($categories, $accessToken, $storeId);
            } catch (\Throwable $e) {
                $extraData = [
                    'category_id' => $category->getId(),
                    'category_name' => $category->getName(),
                    'num_of_stores_for_category' => count($storeIds)
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->getCategoryLoggerContext($storeId, 'category_sync_real_time', $extraData)
                );
            }
        }
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
    private function getCategoryPathName(Category $category, $storeId)
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

    /**
     * Get category MetaData for FB product set
     *
     * @param Category $category
     * @return array|null
     */
    private function getCategoryMetaData(Category $category): ?array
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
     * Save key with a fb product set ID
     *
     * @param Category $category
     * @param string $setId
     * @param int $storeId
     * @throws \Throwable
     */
    private function saveFBProductSetID(Category $category, string $setId, $storeId): void
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
    private function getAllChildrenCategories(
        Category $category,
        $storeId,
        bool $onlyActiveCategories = false
    ): Collection {
        $this->fbeHelper->log("searching children category for " . $category->getName());
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
     * Get all children categories
     *
     * @param Category $category
     * @param int $storeId
     * @return Collection
     * @throws \Throwable
     */
    private function getAllActiveChildrenCategories(Category $category, $storeId): Collection
    {
        $this->fbeHelper->log("searching active children category for " . $category->getName());
        return $this->getAllChildrenCategories($category, $storeId, true);
    }

    /**
     * Get all active categories
     *
     * @param int $storeId
     * @return Collection
     * @throws \Throwable
     */
    private function getAllActiveCategories($storeId): Collection
    {
        $store = $this->systemConfig->getStoreManager()->getStore($storeId);
        $rootCategoryId = $store->getRootCategoryId();
        $rootCategory = $this->categoryRepository->get($rootCategoryId, $storeId);

        return $this->getAllActiveChildrenCategories($rootCategory, $storeId);
    }

    /**
     * Push all categories to FB collections
     *
     * Initial collection call after fbe installation, please not we only push leaf category to collection,
     * this means if a category contains any category, we won't create a collection for it.
     *
     * @param int $storeId
     * @return string|null
     * @throws \Throwable
     */
    public function pushAllCategoriesToFbCollections($storeId): ?string
    {
        $accessToken = $this->systemConfig->getAccessToken($storeId);
        if (!$accessToken) {
            $this->fbeHelper->log(
                "Category force update: can't find access token, abort pushAllCategoriesToFbCollections"
            );
            return null;
        }

        $categories = $this->getAllActiveCategories($storeId);
        $this->fbeHelper->log(
            "Category force update: categories for store:" . $storeId . " count:" . count($categories)
        );

        return $this->pushCategoriesToFBCollections($categories, $accessToken, $storeId);
    }

    /**
     * Push categories to FB collections
     *
     * @param Collection $categories
     * @param string $accessToken
     * @param int $storeId
     * @return string|null
     */
    private function pushCategoriesToFBCollections($categories, $accessToken, $storeId): ?string
    {
        $resArray = [];
        $catalogId = $this->systemConfig->getCatalogId($storeId);
        $requests = [];
        $updatedCategories = [];
        $currentBatch = 1;
        foreach ($categories as $category) {
            try {
                $syncEnabled = $category->getData(SystemConfig::CATEGORY_SYNC_TO_FACEBOOK);
                if ($syncEnabled === '0') {
                    $this->fbeHelper->log(
                        sprintf(
                            "Category update: user disabled category sync, category name: %s for store id: %s",
                            $category->getName(),
                            $storeId
                        )
                    );
                    continue;
                }
                $setId = $category->getData(SystemConfig::META_PRODUCT_SET_ID);
                $this->fbeHelper->log(sprintf(
                    "Category update: setId for CATEGORY %s and store %s is %s",
                    $category->getName(),
                    $storeId,
                    $setId
                ));
                $products = $this->getCategoryProducts($category, $storeId);
                if ($setId) {
                    $this->fbeHelper->log(sprintf(
                        "Category update: Updating FB product set %s for CATEGORY %s and store %s",
                        $setId,
                        $category->getName(),
                        $storeId
                    ));
                    $requests[] = $this->updateCategoryWithFBRequestJson($category, $products, $setId, $storeId);
                } else {
                    if (count($products) === 0) {
                        $this->fbeHelper->log(sprintf(
                            "Category update: Empty CATEGORY %s and store %s, product set creation skipped",
                            $category->getName(),
                            $storeId
                        ));
                        continue;
                    }
                    $this->fbeHelper->log(sprintf(
                        "Category update: Creating new FB product set for CATEGORY %s and store %s",
                        $category->getName(),
                        $storeId
                    ));
                    $requests[] = $this->pushCategoryWithFBRequestJson($category, $products, $catalogId, $storeId);
                }
                $updatedCategories[] = $category;
                if (count($requests) === self::BATCH_MAX) {
                    $resArray = array_merge($resArray,
                        $this->flushCategoryBatchRequest($requests, $updatedCategories,
                            $currentBatch, $accessToken, $storeId));
                    $requests = [];
                    $updatedCategories = [];
                    $currentBatch++;
                }
            } catch (\Throwable $e) {
                $resArray[] = __(
                    "Error occurred while updating product category %1, " .
                    "please check the error log for more details",
                    $category->getName()
                );
                $extraData = [
                    'category_id' => $category->getId(),
                    'category_name' => $category->getName(),
                    'num_categories_for_update' => count($categories)
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->getCategoryLoggerContext($storeId, 'categories_push_to_meta', $extraData)
                );
            }
        }
        if (!empty($requests)) {
            try {
                $resArray = array_merge($resArray,
                    $this->flushCategoryBatchRequest($requests, $updatedCategories,
                        $currentBatch, $accessToken, $storeId));
            } catch (\Throwable $e) {
                $extraData = ['num_categories_for_update' => count($categories)];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->getCategoryLoggerContext(
                        $storeId,
                        'categories_push_to_meta_last_page',
                        $extraData
                    )
                );
            }
        }
        return json_encode($resArray);
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
    private function getCategoryProductFilter(ProductCollection $productCollection): string
    {
        $this->fbeHelper->log("product collection count:" . count($productCollection));

        $ids = [];
        foreach ($productCollection as $product) {
            $ids[] = "'" . $this->productIdentifier->getMagentoProductRetailerId($product) . "'";
        }
        $filter = sprintf("{'retailer_id': {'is_any': [%s]}}", implode(',', $ids));
        $this->fbeHelper->log("filter:" . $filter);

        return $filter;
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
    private function getCategoryProducts(Category $category, $storeId): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStoreId($storeId);
        $productCollection->addAttributeToSelect('sku');
        $productCollection->distinct(true);
        $productCollection->addCategoriesFilter(['eq' => $category->getId()]);
        $productCollection->getSelect()->limit(10000);
        $this->fbeHelper->log("product collection count:" . count($productCollection));

        return $productCollection;
    }

    /**
     * Returns request JSON for product set update batch API
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @param ProductCollection $products
     * @param string $setId
     * @param int $storeId
     * @return array
     */
    private function updateCategoryWithFBRequestJson(
        Category          $category,
        ProductCollection $products,
        string            $setId,
        int               $storeId
    ): array {
        return array(
            'method' => 'POST',
            'relative_url' => $setId,
            'body' => http_build_query([
                'name' => $this->getCategoryPathName($category, $storeId),
                'filter' => $this->getCategoryProductFilter($products),
                'metadata' => $this->getCategoryMetaData($category),
                'retailer_id' => $category->getId()
            ])
        );
    }

    /**
     * Returns request JSON for creating new product set batch API
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @param ProductCollection $products
     * @param string $catalogId
     * @param int $storeId
     * @return array
     */
    private function pushCategoryWithFBRequestJson(
        Category          $category,
        ProductCollection $products,
        string            $catalogId,
        int               $storeId
    ): array {
        return array(
            'method' => 'POST',
            'relative_url' => $catalogId . '/product_sets',
            'body' => http_build_query([
                'name' => $this->getCategoryPathName($category, $storeId),
                'filter' => $this->getCategoryProductFilter($products),
                'metadata' => $this->getCategoryMetaData($category),
                'retailer_id' => $category->getId()
            ])
        );
    }

    /**
     * Flush catalog batch request
     *
     * @param array $requests
     * @param array $updated_categories
     * @param int $currentBatch
     * @param $accessToken
     * @param $storeId
     * @return array
     * @throws \Throwable
     */
    private function flushCategoryBatchRequest(
        array $requests,
        array $updated_categories,
        int   $currentBatch,
        $accessToken,
        $storeId
    ): array {
        $this->fbeHelper->log(sprintf('Pushing batch %d with %d categories', $currentBatch, count($requests)));
        $this->fbeHelper->getGraphAPIAdapter()->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($accessToken);
        $batch_response = $this->fbeHelper->getGraphAPIAdapter()->graphAPIBatchRequest($requests);
        $this->fbeHelper->log('Category push response ' . json_encode($batch_response));
        return $this->processCategoryBatchResponse($batch_response, $updated_categories, $storeId);
    }

    /**
     * function processes category batch response
     *
     * @param array $batchResponse
     * @param array $updatedCategories
     * @param $storeId
     * @return array
     * @throws \Throwable
     */
    private function processCategoryBatchResponse(
        array $batchResponse,
        array $updatedCategories,
        $storeId
    ): array {
        $categoryCount = count($updatedCategories);
        $responseCount = count($batchResponse);

        $responses = [];
        if ($categoryCount === $responseCount) {
            foreach ($updatedCategories as $index => $category) {
                $response = $batchResponse[$index];

                $httpStatusCode = $response['code'];
                $responseData = json_decode($response['body'], true);

                if ($httpStatusCode == 200) {
                    $setId = $category->getData(SystemConfig::META_PRODUCT_SET_ID);

                    if ($setId === null && array_key_exists('id', $responseData)) {
                        $setId = $responseData['id'];
                        $this->saveFBProductSetID($category, $setId, $storeId);
                    }
                } else {
                    $this->fbeHelper->log(sprintf(
                        "Error occurred while syncing category %s, response body %s",
                        $category->getName(),
                        $response['body']
                    ));
                }
                $responses[] = $responseData;
            }
            return $responses;
        } else {
            $this->fbeHelper->log(sprintf(
                "Category batch upload response count: %s is not equal to requested categories count: %s",
                $responseCount,
                $categoryCount
            ));
            return $batchResponse;
        }
    }

    /**
     * Returns request JSON for product set delete batch API
     *
     * API link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param string $setId
     * @return array
     */
    private function deleteCategoryWithFBRequestJson(string $setId): array
    {
        return [
            'method' => 'DELETE',
            'relative_url' => $setId,
        ];
    }

    /**
     * Call the API delete existing product set under category
     *
     * When user deletes a category on magento, we first get all sub categories(including itself), and check if we
     * have created a collection set on fb side, if yes then we make delete api call.
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @return void
     * @throws \Throwable
     */
    public function deleteCategoryAndSubCategoryFromFB(Category $category): void
    {
        $storeIds = $category->getStoreIds();
        $this->fbeHelper->log("Delete Categories: store counts: " . count($storeIds));
        foreach ($storeIds as $storeId) {
            $accessToken = $this->systemConfig->getAccessToken($storeId);
            if ($accessToken === null) {
                $this->fbeHelper->log(sprintf(
                    "can't find access token, won't do category delete, store: %s",
                    $storeId
                ));
                continue;
            }

            $childrenCategories = $this->getAllChildrenCategories($category, $storeId);
            $requests = [];
            $currentBatch = 1;
            foreach ($childrenCategories as $childrenCategory) {
                try {
                    $this->fbeHelper->log(sprintf(
                        "Deleted category name: %s, store: %s",
                        $childrenCategory->getName(),
                        $storeId
                    ));

                    $setId = $childrenCategory->getData(SystemConfig::META_PRODUCT_SET_ID);
                    if ($setId == null) {
                        $this->fbeHelper->log(sprintf(
                            "cant find product set id, won't make category delete api, store: %s",
                            $storeId
                        ));
                        continue;
                    }

                    $requests[] = $this->deleteCategoryWithFBRequestJson($setId);

                    if (count($requests) === self::BATCH_MAX) {
                        $this->flushCategoryDeleteBatchRequest($requests, $currentBatch, $accessToken, $storeId);

                        $requests = [];
                        $currentBatch++;
                    }
                } catch (\Throwable $e) {
                    $extraData = [
                        'category_id' => $category->getId(),
                        'category_name' => $category->getName(),
                        'num_categories_for_delete' => count($childrenCategories)
                    ];
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        $this->getCategoryLoggerContext($storeId, 'delete_categories', $extraData)
                    );
                }
            }

            if (!empty($requests)) {
                try {
                    $this->flushCategoryDeleteBatchRequest($requests, $currentBatch, $accessToken, $storeId);
                } catch (\Throwable $e) {
                    $extraData = [
                        'category_id' => $category->getId(),
                        'category_name' => $category->getName(),
                        'num_categories_for_delete' => count($childrenCategories)
                    ];
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        $this->getCategoryLoggerContext($storeId, 'delete_categories_last_page', $extraData)
                    );
                }
            }
        }
    }

    /**
     * Flush catalog batch request
     *
     * @param array $requests
     * @param int $currentBatch
     * @param string $accessToken
     * @param int $storeId
     * @return void
     * @throws \Throwable
     */
    private function flushCategoryDeleteBatchRequest(
        array $requests,
        int   $currentBatch,
        $accessToken,
        $storeId
    ): void {
        $this->fbeHelper->log(sprintf(
            'Deleting Product set batch %d with %d categories',
            $currentBatch,
            count($requests)
        ));
        $this->fbeHelper->getGraphAPIAdapter()->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($accessToken);
        $batchResponse = $this->fbeHelper->getGraphAPIAdapter()->graphAPIBatchRequest($requests);
        $this->fbeHelper->log('Category delete batch response ' . json_encode($batchResponse));

        foreach ($batchResponse as $response) {
            $httpStatusCode = $response['code'];
            $responseData = json_decode($response['body'], true);

            if ($httpStatusCode == 200) {
                if (!array_key_exists('success', $responseData) || !$responseData['success']) {
                    $this->fbeHelper->log("product set deletion failed!!! ");
                } else {
                    $this->fbeHelper->log("product set deletion success!!! ");
                }
            } else {
                $this->fbeHelper->log(sprintf(
                    "Error occurred while deleting product set: response body %s",
                    $response['body']
                ));
            }
        }
    }

    /**
     * Return Category logger context for be logged
     *
     * @param int $storeId
     * @param string $eventType
     * @param array $extraData
     * @return array
     */
    private function getCategoryLoggerContext($storeId, $eventType, $extraData): array
    {
        return [
            'store_id' => $storeId,
            'event' => 'category_sync',
            'event_type' => $eventType,
            'catalog_id' => $this->systemConfig->getCatalogId($storeId),
            'extra_data' => $extraData
        ];
    }
}
