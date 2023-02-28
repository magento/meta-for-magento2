<?php
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

use Exception;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\AbstractAjax;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Uploader;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductFeedUpload extends AbstractAjax
{
    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Construct
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        Uploader $uploader
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->uploader = $uploader;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Execute for json
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function executeForJson()
    {
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
                sprintf(
                    'Before uploading products, set up the extension for \'%s\'.',
                    $storeName
                )
            );
            return $response;
        }

        try {
            $feedPushResponse = $this->uploader->uploadFullCatalog($storeId);
            $response['success'] = true;
            $response['feed_push_response'] = $feedPushResponse;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
