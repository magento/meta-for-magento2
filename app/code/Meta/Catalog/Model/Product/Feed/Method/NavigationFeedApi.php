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

namespace Meta\Catalog\Model\Product\Feed\Method;

use Exception;
use Magento\Catalog\Api\CategoryManagementInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Meta\Catalog\Model\Category\CategoryUtility\CategoryUtilities;

class NavigationFeedApi
{
    private const FEED_FILE_NAME = 'facebook_navigation%s.json';
    private const VAR_DIR = 'var';

    /**
     * @var int
     */
    public int $storeId;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;
    /**
     * @var SystemConfig
     */
    public SystemConfig $systemConfig;

    /**
     * @var Filesystem
     */
    public Filesystem $fileSystem;
    /**
     * @var CategoryUtilities
     */
    private CategoryUtilities $categoryUtilities;
    /**
     * @var CategoryManagementInterface
     */
    private CategoryManagementInterface $categoryManagement;

    /**
     * @param SystemConfig $systemConfig
     * @param FBEHelper $helper
     * @param Filesystem $filesystem
     * @param CategoryUtilities $categoryUtilities
     * @param CategoryManagementInterface $categoryManagement
     */
    public function __construct(
        SystemConfig       $systemConfig,
        FBEHelper                   $helper,
        Filesystem         $filesystem,
        CategoryUtilities           $categoryUtilities,
        CategoryManagementInterface $categoryManagement
    ) {
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $helper;
        $this->fileSystem = $filesystem;
        $this->categoryUtilities = $categoryUtilities;
        $this->categoryManagement = $categoryManagement;
    }

    /**
     * Get file name with store code suffix for non-default store (no suffix for default one)
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFeedFileName(): string
    {
        $defaultStoreId = $this->systemConfig->getStoreManager()->getDefaultStoreView()->getId();
        $storeCode = $this->systemConfig->getStoreManager()->getStore($this->storeId)->getCode();
        return sprintf(
            self::FEED_FILE_NAME,
            ($this->storeId && $this->storeId !== $defaultStoreId) ? ('_' . $storeCode) : ''
        );
    }

    /**
     * Generate navigation feed
     *
     * @param $jsTreeData
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function generateNavigationFeed($jsTreeData): string
    {
        $filePath = 'export/' . $this->getFeedFileName();
        $directory = $this->fileSystem->getDirectoryWrite(self::VAR_DIR);
        $directory->create('export');
        $directory->writeFile($filePath, $jsTreeData);
        return $directory->getAbsolutePath($filePath);
    }

    /**
     * Execute
     *
     * @param int|null $storeId
     * @return bool|mixed
     * @throws Exception
     */
    public function execute($flowName, ?int $storeId = null): mixed
    {
        $this->storeId = $storeId;
        $store = $this->systemConfig->getStoreManager()->getStore($storeId);
        $traceId = $this->fbeHelper->genUniqueTraceID();
        $storeRootCategoryId = $store->getRootCategoryId();

        $context = $this->categoryUtilities->getCategoryLoggerContext(
            $storeId,
            'sync_navigation_menu', /* event_type */
            $flowName,
            'sync_navigation_for_store_start',
            [
                'external_trace_id' => $traceId,
                'root_category_id' => $storeRootCategoryId,
            ]
        );

        $this->fbeHelper->logTelemetryToMeta(
            sprintf(
                "Sync Navigation started: storeId: %d, categoryId: %s, flow: %s",
                $storeId,
                $storeRootCategoryId,
                $flowName
            ),
            $context
        );

        try {
            // Step 1. Generate JSON tree
            $rootCategoryTree = $this->categoryManagement->getTree($storeRootCategoryId);
            $jsTreeData = $this->categoryUtilities->convertJsTreeToMetaRequestFormat($rootCategoryTree);

            // Step 2. Create Json file
            $file = $this->generateNavigationFeed(json_encode($jsTreeData));

            // Step 3. Make graph API call
            $this->fbeHelper->getGraphAPIAdapter()->setDebugMode($this->systemConfig->isDebugMode($storeId))
                ->setAccessToken($this->systemConfig->getAccessToken($storeId));
            $commercePartnerIntegrationId = $this->systemConfig->getCommercePartnerIntegrationId($storeId);
            $response =  $this->fbeHelper->getGraphAPIAdapter()->uploadFile(
                $commercePartnerIntegrationId,
                $file,
                'NAVIGATION_MENU',
                'create'
            );

            $context = $this->categoryUtilities->getCategoryLoggerContext(
                $storeId,
                'sync_navigation_menu', /* event_type */
                $flowName,
                'sync_navigation_for_store_completed',
                [
                    'external_trace_id' => $traceId,
                    'root_category_id' => $storeRootCategoryId,
                ]
            );

            $this->fbeHelper->logTelemetryToMeta(
                sprintf(
                    "Sync Navigation completed: storeId: %d, categoryId: %s, flow: %s",
                    $storeId,
                    $storeRootCategoryId,
                    $flowName
                ),
                $context
            );
            return $response;
        } catch(Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                $this->categoryUtilities->getCategoryLoggerContext(
                    (int)$storeId,
                    'sync_navigation_menu',
                    $flowName,
                    'sync_navigation_for_store_error',
                    [
                        'external_trace_id' => $traceId
                    ]
                )
            );
        }
        return null;
    }
}
