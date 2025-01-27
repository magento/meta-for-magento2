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

use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Category\CategoryUtility\CategoryUtilities;
use Meta\Catalog\Model\Product\Feed\Method\NavigationFeedApi;

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

    private NavigationFeedApi $navigationFeedApi;

    /**
     * Constructor
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     * @param CategoryUtilities $categoryUtilities
     * @param NavigationFeedApi $navigationFeedApi
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        FBEHelper                   $helper,
        SystemConfig                $systemConfig,
        CategoryUtilities           $categoryUtilities,
        NavigationFeedApi $navigationFeedApi
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
        $this->categoryUtilities = $categoryUtilities;
        $this->navigationFeedApi = $navigationFeedApi;
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
        $flowName = 'category_update_real_time';
        $traceId = $this->fbeHelper->genUniqueTraceID();

        if ($this->systemConfig->isAllCategoriesSyncEnabled()) {
            // fetches all the stores for seller
            $storeIds = $this->categoryUtilities->getAllFBEInstalledStoreIds();
        } else {
            $storeIds = $category->getStoreIds();
        }
        foreach ($storeIds as $storeId) {
            try {
                if (!$this->systemConfig->isCatalogSyncEnabled($storeId)) {
                    continue;
                }
                $accessToken = $this->systemConfig->getAccessToken($storeId);
                if ($accessToken === null) {
                    continue;
                }
                $startTime = $this->fbeHelper->getCurrentTimeInMS();
                $context = $this->categoryUtilities->getCategoryLoggerContext(
                    (int)$storeId,
                    '', /* event_type */
                    $flowName,
                    'category_update_sync_real_time_start',
                    [
                        'external_trace_id' => $traceId,
                        'category_id' => $category->getId(),
                        'category_name' => $category->getName()
                    ]
                );
                $this->fbeHelper->logTelemetryToMeta(
                    sprintf(
                        "Category real time update: categoryId: %s, storeId: %s, flow: %s",
                        $category->getId(),
                        $storeId,
                        $flowName
                    ),
                    $context
                );

                $categories = [];
                if ($isNameChanged) {
                    $categories = $this->categoryUtilities->getAllChildrenCategories(
                        $category,
                        (int)$storeId,
                        $flowName,
                        $traceId
                    );
                } else {
                    $categories[] = $this->categoryRepository->get($category->getId(), $storeId);
                }
                $this->pushCategoriesToFBCollections($categories, $accessToken, (int)$storeId, $flowName, $traceId);

                $context = $this->categoryUtilities->getCategoryLoggerContext(
                    (int)$storeId,
                    '', /* event_type */
                    $flowName,
                    'category_update_sync_real_time_completed',
                    [
                        'external_trace_id' => $traceId,
                        'time_taken' => $this->fbeHelper->getCurrentTimeInMS() - $startTime
                    ]
                );

                $this->fbeHelper->logTelemetryToMeta(
                    sprintf(
                        "Category real time update completed: categoryId: %s, storeId: %s, flow: %s",
                        $category->getId(),
                        $storeId,
                        $flowName
                    ),
                    $context
                );
            } catch (\Throwable $e) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->categoryUtilities->getCategoryLoggerContext(
                        (int)$storeId,
                        'category_sync_real_time',
                        $flowName,
                        'category_update_sync_real_time_error',
                        [
                            'external_trace_id' => $traceId
                        ]
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
     * @param string $flowName
     * @param string $traceId
     * @return string|null
     * @throws NoSuchEntityException
     * @throws \Throwable
     */
    public function pushAllCategoriesToFbCollections(int $storeId, string $flowName, string $traceId): ?string
    {
        $accessToken = $this->systemConfig->getAccessToken($storeId);
        if (!$accessToken) {
            return null;
        }

        $startTime = $this->fbeHelper->getCurrentTimeInMS();
        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'all_categories_sync_for_store_starts',
            [
                'external_trace_id' => $traceId,
                'all_categories_sync_enabled' => $this->systemConfig->isAllCategoriesSyncEnabled()
            ]
        );

        $this->fbeHelper->logTelemetryToMeta(
            sprintf("All Categories sync starts: storeId: %s ,flow: %s", $storeId, $flowName),
            $context
        );

        if ($this->systemConfig->isAllCategoriesSyncEnabled()) {
            $categories = $this->categoryUtilities->getAllCategoriesForSeller($storeId, $flowName, $traceId);
        } else {
            $categories = $this->categoryUtilities->getAllCategoriesForStore($storeId, $flowName, $traceId);
        }

        $response = $this->pushCategoriesToFBCollections($categories, $accessToken, $storeId, $flowName, $traceId);

        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'all_categories_sync_for_store_completed',
            [
                'external_trace_id' => $traceId,
                'time_taken' => $this->fbeHelper->getCurrentTimeInMS() - $startTime
            ]
        );

        $this->fbeHelper->logTelemetryToMeta(
            sprintf("All Categories sync completed: storeId: %s ,flow: %s", $storeId, $flowName),
            $context
        );

        // Push Navigation Tree to Meta after Sync Collection
        $this->pushNavigationTreeToMeta($storeId);

        return $response;
    }

    /**
     * @throws \Exception
     */
    private function pushNavigationTreeToMeta(int $storeId): void {
        $this->navigationFeedApi->execute( 'push_navigation_tree', $storeId);
    }

    /**
     * Push categories to FB collections
     *
     * @param Collection $categories
     * @param string $accessToken
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return string|null
     *
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function pushCategoriesToFBCollections(
        $categories,
        string $accessToken,
        int $storeId,
        string $flowName,
        string $traceId
    ): ?string {
        $startTime = $this->fbeHelper->getCurrentTimeInMS();
        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'categories_sync_to_meta_started',
            [
                'external_trace_id' => $traceId
            ]
        );
        $this->fbeHelper->logTelemetryToMeta(
            sprintf("Category sync, push categories to Meta starts: storeId: %s, flow: %s", $storeId, $flowName),
            $context
        );

        $resArray = [];
        $catalogId = $this->systemConfig->getCatalogId($storeId);
        $requests = [];
        $updatedCategories = [];
        $currentBatch = 1;

        $store = $this->systemConfig->getStoreManager()->getStore($storeId);
        $storeRootCategoryId = $store->getRootCategoryId();
        $isAllCategoriesSyncEnabled = $this->systemConfig->isAllCategoriesSyncEnabled();

        foreach ($categories as $category) {
            try {
                $rootCategoryId = $this->categoryUtilities->getRootCategoryIdForCategory($category);
                $isCategoryLinkedToStore = $rootCategoryId == $storeRootCategoryId;

                $isVisibleOnMeta = $this->categoryUtilities->isCategoryVisibleOnMeta(
                    $category,
                    $isCategoryLinkedToStore
                );

                if (!$isVisibleOnMeta && !$isAllCategoriesSyncEnabled) {
                    continue;
                }

                $setId = $category->getData(SystemConfig::META_PRODUCT_SET_ID);
                $products = $this->categoryUtilities->getCategoryProducts($category, $storeId);
                if ($setId) {
                    $requests[] = $this->updateCategoryWithFBRequestJson(
                        $category,
                        $products,
                        $setId,
                        $storeId,
                        $isVisibleOnMeta
                    );
                } else {
                    if ($products->getSize() === 0) {
                        $this->fbeHelper->log(sprintf(
                            "Category update: Empty CATEGORY %s and store %s, product set creation skipped",
                            $category->getName(),
                            $storeId
                        ));
                        continue;
                    }
                    $requests[] = $this->pushCategoryWithFBRequestJson(
                        $category,
                        $products,
                        $catalogId,
                        $storeId,
                        $isVisibleOnMeta
                    );
                }
                $updatedCategories[] = $category;
                if (count($requests) === self::BATCH_MAX) {
                    $batchResponse = $this->flushCategoryBatchRequest(
                        $requests,
                        $updatedCategories,
                        $currentBatch,
                        $accessToken,
                        $storeId,
                        $flowName,
                        $traceId
                    );
                    array_push($resArray, ...$batchResponse);
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
                    'external_trace_id' => $traceId,
                    'batch_num' => $currentBatch
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    $this->categoryUtilities->getCategoryLoggerContext(
                        $storeId,
                        'categories_push_to_meta',
                        $flowName,
                        'categories_sync_to_meta_error',
                        $extraData
                    )
                );
            }
        }
        if (!empty($requests)) {
            $batchResponse = $this->flushCategoryBatchRequest(
                $requests,
                $updatedCategories,
                $currentBatch,
                $accessToken,
                $storeId,
                $flowName,
                $traceId
            );
            array_push($resArray, ...$batchResponse);
        }
        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'categories_sync_to_meta_completed',
            [
                'external_trace_id' => $traceId,
                'time_taken' => $this->fbeHelper->getCurrentTimeInMS() - $startTime
            ]
        );
        $this->fbeHelper->logTelemetryToMeta(
            sprintf("Category sync, push categories to Meta ends: storeId: %s, flow: %s", $storeId, $flowName),
            $context
        );
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
     * @param bool $isVisibleOnMeta
     * @return array
     */
    private function updateCategoryWithFBRequestJson(
        Category          $category,
        ProductCollection $products,
        string            $setId,
        int               $storeId,
        bool              $isVisibleOnMeta
    ): array {
        return [
            'method' => 'POST',
            'relative_url' => $setId,
            'body' => http_build_query([
                'name' => $category->getName(),
                'filter' => $this->categoryUtilities->getCategoryProductFilter($products),
                'metadata' => $this->categoryUtilities->getCategoryMetaData($category),
                'retailer_id' => $category->getId(),
                'visibility' => $isVisibleOnMeta
                    ? CategoryUtilities::CATEGORY_VISIBLE_FOR_META
                    : CategoryUtilities::CATEGORY_HIDDEN_FOR_META
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
     * @param bool $isVisibleOnMeta
     * @return array
     */
    private function pushCategoryWithFBRequestJson(
        Category          $category,
        ProductCollection $products,
        string            $catalogId,
        int               $storeId,
        bool              $isVisibleOnMeta
    ): array {
        return [
            'method' => 'POST',
            'relative_url' => $catalogId . '/product_sets',
            'body' => http_build_query([
                'name' => $this->categoryUtilities->getCategoryPathName($category, $storeId, $isVisibleOnMeta),
                'filter' => $this->categoryUtilities->getCategoryProductFilter($products),
                'metadata' => $this->categoryUtilities->getCategoryMetaData($category),
                'retailer_id' => $category->getId(),
                'visibility' => $isVisibleOnMeta
                    ? CategoryUtilities::CATEGORY_VISIBLE_FOR_META
                    : CategoryUtilities::CATEGORY_HIDDEN_FOR_META
            ])
        ];
    }

    /**
     * Flush catalog batch request
     *
     * @param array $requests
     * @param array $updatedCategories
     * @param int $currentBatch
     * @param string $accessToken
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return array
     */
    private function flushCategoryBatchRequest(
        array  $requests,
        array  $updatedCategories,
        int    $currentBatch,
        string $accessToken,
        int    $storeId,
        string $flowName,
        string $traceId
    ): array {
        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'category_sync_flush_batch_request',
            [
                'external_trace_id' => $traceId,
                'batch_num' => $currentBatch,
                'batch_size' => count($updatedCategories),
            ]
        );
        $this->fbeHelper->logTelemetryToMeta(
            sprintf(
                'Pushing category batch: BatchNum %d, CategoryCount: %d, flow: %s ',
                $currentBatch,
                count($requests),
                $flowName
            ),
            $context
        );

        try {
            $this->fbeHelper->getGraphAPIAdapter()->setDebugMode($this->systemConfig->isDebugMode($storeId))
                ->setAccessToken($accessToken);
            $batchResponse = $this->fbeHelper->getGraphAPIAdapter()->graphAPIBatchRequest($requests);

            return $this->processCategoryBatchResponse(
                $batchResponse,
                $updatedCategories,
                $storeId,
                $currentBatch,
                $flowName,
                $traceId
            );
        } catch (\Throwable $e) {
            $categoryIds = [];
            foreach ($updatedCategories as $category) {
                $categoryIds[] = $category->getId();
            }
            $resArray[] = __(
                "Error occurred while updating product categories: %1," .
                " please check the error log for more details",
                json_encode($categoryIds)
            );

            $extraData = [
                'external_trace_id' => $traceId,
                'batch_num' => $currentBatch,
                'batch_size' => count($updatedCategories),
                'categories' => json_encode($categoryIds)
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                $this->categoryUtilities->getCategoryLoggerContext(
                    $storeId,
                    'category_push_batch_request_error',
                    $flowName,
                    'category_sync_batch_request_error',
                    $extraData
                )
            );

            return $resArray;
        }
    }

    /**
     * Function processes category batch response
     *
     * @param array $batchResponse
     * @param array $updatedCategories
     * @param int $storeId
     * @param int $batchNum
     * @param string $flowName
     * @param string $traceId
     * @return array
     * @throws \Throwable
     */
    private function processCategoryBatchResponse(
        array  $batchResponse,
        array  $updatedCategories,
        int    $storeId,
        int    $batchNum,
        string $flowName,
        string $traceId
    ): array {
        $categoryCount = count($updatedCategories);
        $responseCount = count($batchResponse);

        $responses = [];
        if ($categoryCount === $responseCount) {
            $categorySaveErrorCount = 0;
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
                    $categorySaveErrorCount++;
                }
                $responses[] = $responseData;
            }

            $context = $this->categoryUtilities->getCategoryLoggerContext(
                $storeId,
                '', /* event_type */
                $flowName,
                'category_sync_batch_response_processed',
                [
                    'external_trace_id' => $traceId,
                    'batch_num' => $batchNum,
                    'batch_size' => $categoryCount,
                    'category_save_error_count' => $categorySaveErrorCount,
                    'category_upload_response' => json_encode($batchResponse)
                ]
            );
            $this->fbeHelper->logTelemetryToMeta(
                sprintf(
                    "Category batch response processed: BatchNum: %d, " .
                    "CategoriesCount: %d, CategorySaveErrorCount: %d, flow: %s",
                    $batchNum,
                    $responseCount,
                    $categorySaveErrorCount,
                    $flowName
                ),
                $context
            );
            return $responses;
        } else {
            $context = $this->categoryUtilities->getCategoryLoggerContext(
                $storeId,
                '', /* event_type */
                $flowName,
                'category_sync_batch_response_size_mismatch',
                [
                    'external_trace_id' => $traceId,
                    'batch_num' => $batchNum,
                    'response_size' => $responseCount,
                    'batch_size' => $categoryCount
                ]
            );
            $this->fbeHelper->logTelemetryToMeta(
                sprintf(
                    "Category batch response error, response count not equal" .
                     "category count: BatchNum: %d, ResponseCount: %d, CategoriesCount: %d, flow: %s",
                    $batchNum,
                    $responseCount,
                    $categoryCount,
                    $flowName
                ),
                $context
            );
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function deleteCategoryAndSubCategoryFromFB(Category $category): void
    {
        $flowName = 'category_delete_real_time';
        $traceId = $this->fbeHelper->genUniqueTraceID();

        $storeIds = $this->categoryUtilities->getAllFBEInstalledStoreIds();
        $this->fbeHelper->log("Delete Categories: store counts: " . count($storeIds));
        foreach ($storeIds as $storeId) {
            $accessToken = $this->systemConfig->getAccessToken($storeId);
            if ($accessToken === null) {
                continue;
            }

            $startTime = $this->fbeHelper->getCurrentTimeInMS();
            $context = $this->categoryUtilities->getCategoryLoggerContext(
                $storeId,
                '', /* event_type */
                $flowName,
                'category_and_children_delete_for_store_start',
                [
                    'external_trace_id' => $traceId,
                    'root_category_id' => $category->getId()
                ]
            );

            $this->fbeHelper->logTelemetryToMeta(
                sprintf(
                    "Delete Categories started: storeId: %d, categoryId: %s, flow: %s",
                    $storeId,
                    $category->getId(),
                    $flowName
                ),
                $context
            );

            $childrenCategories = $this->categoryUtilities->getAllChildrenCategories(
                $category,
                $storeId,
                $flowName,
                $traceId
            );
            $requests = [];
            $currentBatch = 1;
            foreach ($childrenCategories as $childrenCategory) {
                try {
                    $setId = $childrenCategory->getData(SystemConfig::META_PRODUCT_SET_ID);
                    if ($setId == null) {
                        continue;
                    }
                    $requests[] = $this->deleteCategoryWithFBRequestJson($setId);
                    if (count($requests) === self::BATCH_MAX) {
                        $this->flushCategoryDeleteBatchRequest(
                            $requests,
                            $currentBatch,
                            $accessToken,
                            $storeId,
                            $flowName,
                            $traceId
                        );
                        $requests = [];
                        $currentBatch++;
                    }
                } catch (\Throwable $e) {
                    $extraData = [
                        'category_id' => $category->getId(),
                        'category_name' => $category->getName(),
                        'num_categories_for_delete' => $childrenCategories->getSize(),
                        'external_trace_id' => $traceId
                    ];
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        $this->categoryUtilities->getCategoryLoggerContext(
                            $storeId,
                            'delete_categories',
                            $flowName,
                            'delete_categories_error',
                            $extraData
                        )
                    );
                }
            }
            if (!empty($requests)) {
                try {
                    $this->flushCategoryDeleteBatchRequest(
                        $requests,
                        $currentBatch,
                        $accessToken,
                        $storeId,
                        $flowName,
                        $traceId
                    );
                } catch (\Throwable $e) {
                    $extraData = [
                        'category_id' => $category->getId(),
                        'category_name' => $category->getName(),
                        'num_categories_for_delete' => $childrenCategories->getSize(),
                        'external_trace_id' => $traceId
                    ];
                    $this->fbeHelper->logExceptionImmediatelyToMeta(
                        $e,
                        $this->categoryUtilities->getCategoryLoggerContext(
                            $storeId,
                            'delete_categories_last_page',
                            $flowName,
                            'delete_categories_error_last_page',
                            $extraData
                        )
                    );
                }
            }

            $context = $this->categoryUtilities->getCategoryLoggerContext(
                $storeId,
                '', /* event_type */
                $flowName,
                'category_and_children_delete_for_store_completed',
                [
                    'external_trace_id' => $traceId,
                    'root_category_id' => $category->getId(),
                    'time_taken' => $this->fbeHelper->getCurrentTimeInMS() - $startTime
                ]
            );

            $this->fbeHelper->logTelemetryToMeta(
                sprintf(
                    "Delete Categories completed: storeId: %d, categoryId: %s, flow: %s",
                    $storeId,
                    $category->getId(),
                    $flowName
                ),
                $context
            );
        }
    }

    /**
     * Flush catalog batch request
     *
     * @param array $requests
     * @param int $currentBatch
     * @param string $accessToken
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return void
     * @throws GuzzleException
     */
    private function flushCategoryDeleteBatchRequest(
        array $requests,
        int   $currentBatch,
        string $accessToken,
        int   $storeId,
        string $flowName,
        string $traceId
    ): void {
        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'category_flush_delete_batch_request',
            [
                'external_trace_id' => $traceId,
                'batch_num' => $currentBatch,
                'batch_size' => count($requests),
            ]
        );
        $this->fbeHelper->logTelemetryToMeta(
            sprintf(
                'Pushing delete category batch: BatchNum %d, BatchSize: %d, flow: %s',
                $currentBatch,
                count($requests),
                $flowName
            ),
            $context
        );
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
