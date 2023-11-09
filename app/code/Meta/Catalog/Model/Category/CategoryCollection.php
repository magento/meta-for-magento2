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

namespace Meta\Catalog\Model\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Category\CategoryUtility\CategoryUtilities;

class CategoryCollection
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CategoryUtilities
     */
    private CategoryUtilities $categoryUtilities;

    private const BATCH_MAX = 49;

    /**
     * Constructor
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     * @param CategoryUtilities $categoryUtilities
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        FBEHelper                   $helper,
        SystemConfig                $systemConfig,
        CategoryUtilities $categoryUtilities
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
        $this->categoryUtilities = $categoryUtilities;
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
                    $categories = $this->categoryUtilities->getAllChildrenCategories(
                        $category,
                        $storeId,
                        true
                    );
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
                    $this->categoryUtilities->getCategoryLoggerContext(
                        $storeId,
                        'category_sync_real_time',
                        $extraData
                    )
                );
            }
        }
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

        $categories = $this->categoryUtilities->getAllActiveCategories($storeId);
        $this->fbeHelper->log(
            "Category force update: categories for store:" . $storeId . " count:" . $categories->getSize()
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                $products = $this->categoryUtilities->getCategoryProducts($category, $storeId);
                if ($setId) {
                    $this->fbeHelper->log(sprintf(
                        "Category update: Updating FB product set %s for CATEGORY %s and store %s",
                        $setId,
                        $category->getName(),
                        $storeId
                    ));
                    $requests[] = $this->updateCategoryWithFBRequestJson($category, $products, $setId, (int)$storeId);
                } else {
                    if ($products->getSize() === 0) {
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
                    $requests[] = $this->pushCategoryWithFBRequestJson($category, $products, $catalogId, (int)$storeId);
                }
                $updatedCategories[] = $category;
                if (count($requests) === self::BATCH_MAX) {
                    // TODO: phpcs: array_merge(...) is used in a loop and is a resources greedy construction.
                    $resArray = array_merge(
                        $resArray,
                        $this->flushCategoryBatchRequest(
                            $requests,
                            $updatedCategories,
                            $currentBatch,
                            $accessToken,
                            $storeId
                        )
                    );
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
                    'category_name' => $category->getName()
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->categoryUtilities->getCategoryLoggerContext(
                        $storeId,
                        'categories_push_to_meta',
                        $extraData
                    )
                );
            }
        }
        if (!empty($requests)) {
            try {
                $resArray = array_merge(
                    $resArray,
                    $this->flushCategoryBatchRequest(
                        $requests,
                        $updatedCategories,
                        $currentBatch,
                        $accessToken,
                        $storeId
                    )
                );
            } catch (\Throwable $e) {
                $extraData = [];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->categoryUtilities->getCategoryLoggerContext(
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
        return [
            'method' => 'POST',
            'relative_url' => $setId,
            'body' => http_build_query([
                'name' => $this->categoryUtilities->getCategoryPathName($category, $storeId),
                'filter' => $this->categoryUtilities->getCategoryProductFilter($products),
                'metadata' => $this->categoryUtilities->getCategoryMetaData($category),
                'retailer_id' => $category->getId()
            ])
        ];
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
        return [
            'method' => 'POST',
            'relative_url' => $catalogId . '/product_sets',
            'body' => http_build_query([
                'name' => $this->categoryUtilities->getCategoryPathName($category, $storeId),
                'filter' => $this->categoryUtilities->getCategoryProductFilter($products),
                'metadata' => $this->categoryUtilities->getCategoryMetaData($category),
                'retailer_id' => $category->getId()
            ])
        ];
    }

    /**
     * Flush catalog batch request
     *
     * @param array $requests
     * @param array $updated_categories
     * @param int $currentBatch
     * @param string $accessToken
     * @param int $storeId
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
     * Function processes category batch response
     *
     * @param array $batchResponse
     * @param array $updatedCategories
     * @param int $storeId
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
                    if (array_key_exists('id', $responseData)) {
                        $setId = $responseData['id'];
                        $this->categoryUtilities->saveFBProductSetID($category, $setId, $storeId);
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
            $childrenCategories = $this->categoryUtilities->getAllChildrenCategories($category, $storeId);
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
                        'num_categories_for_delete' => $childrenCategories->getSize()
                    ];
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        $this->categoryUtilities->getCategoryLoggerContext(
                            $storeId,
                            'delete_categories',
                            $extraData
                        )
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
                        'num_categories_for_delete' => $childrenCategories->getSize()
                    ];
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        $this->categoryUtilities->getCategoryLoggerContext(
                            $storeId,
                            'delete_categories_last_page',
                            $extraData
                        )
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
}
