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

namespace Meta\Catalog\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\AbstractAjax;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Category\CategoryCollection;

class CategoryUpload extends AbstractAjax
{
    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param CategoryCollection $categoryCollection
     */
    public function __construct(
        Context            $context,
        JsonFactory        $resultJsonFactory,
        FBEHelper          $fbeHelper,
        SystemConfig       $systemConfig,
        CategoryCollection $categoryCollection
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->categoryCollection = $categoryCollection;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * @inheritDoc
     */
    public function executeForJson()
    {
        $traceId = $this->fbeHelper->genUniqueTraceID();
        $flowName = 'categories_force_update';

        $response = [];

        // get default store info
        $storeId = $this->fbeHelper->getStore()->getId();
        $storeName = $this->fbeHelper->getStore()->getName();

        // override store if user switched config scope to non-default
        $storeParam = $this->getRequest()->getParam('store');
        if ($storeParam) {
            $storeId = $storeParam;
            $storeName = $this->systemConfig->getStoreManager()->getStore($storeId)->getName();
        }

        if (!$this->systemConfig->getAccessToken($storeId)) {
            $response['success'] = false;
            $response['message'] = __(
                'Before uploading categories, set up the extension for store: \'%1\'.',
                $storeName
            );
            $this->fbeHelper->log(sprintf(
                'Force Categories update: extension is not setup for store: %s',
                $storeId
            ));
            return $response;
        }

        if (!$this->systemConfig->isCatalogSyncEnabled($storeId)) {
            $response['success'] = false;
            $response['message'] = __(
                'Catalog sync is not enabled for store \'%1\', ' .
                'please enable meta catalog integration for categories sync.',
                $storeName
            );
            $this->fbeHelper->log(sprintf(
                'Force Categories update: catalog sync is not enabled or either' .
                ' Meta extension is disabled for store: %s',
                $storeId
            ));
            return $response;
        }

        try {
            $feedPushResponse = $this->categoryCollection->pushAllCategoriesToFbCollections(
                (int)$storeId,
                $flowName,
                $traceId
            );
            $response['success'] = true;
            $response['feed_push_response'] = $feedPushResponse;
        } catch (\Throwable $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'category_sync',
                    'event_type' => 'all_categories_force_sync',
                    'flow_name' => $flowName,
                    'flow_step' => 'categories_force_update_error',
                    'catalog_id' => $this->systemConfig->getCatalogId($storeId),
                    [
                        'external_trace_id' => $traceId
                    ]
                ]
            );
        }
        return $response;
    }
}
