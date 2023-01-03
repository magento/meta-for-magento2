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

namespace Meta\BusinessExtension\Controller\Adminhtml\Ajax;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class PersistConfiguration extends AbstractAjax
{
    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper, $systemConfig);
        $this->graphApiAdapter = $graphApiAdapter;
    }

    public function executeForJson()
    {
        try {
            $accessToken = $this->getRequest()->getParam('accessToken');
            $externalBusinessId = $this->getRequest()->getParam('externalBusinessId');
            $catalogId = $this->getRequest()->getParam('catalogId');
            $pageId = $this->getRequest()->getParam('pageId');

            $this->saveExternalBusinessId($externalBusinessId)
                ->saveCatalogId($catalogId)
                ->completeOnsiteOnboarding($accessToken, $pageId);

            $response['success'] = true;
            $response['message'] = 'Configuration successfully saved';
            return $response;
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->_fbeHelper->logException($e);
            return $response;
        }
    }

    /**
     * @param $catalogId
     * @return $this
     */
    public function saveCatalogId($catalogId)
    {
        if ($catalogId) {
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $catalogId);
            $this->_fbeHelper->log('Catalog ID saved on instance --- '. $catalogId);
        }
        return $this;
    }

    /**
     * @param $externalBusinessId
     * @return $this
     */
    public function saveExternalBusinessId($externalBusinessId)
    {
        if ($externalBusinessId) {
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID, $externalBusinessId);
            $this->_fbeHelper->log('External business ID saved on instance --- '. $externalBusinessId);
        }
        return $this;
    }

    /**
     * @param $accessToken
     * @param $pageId
     * @return $this
     * @throws LocalizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function completeOnsiteOnboarding($accessToken, $pageId)
    {
        if (!$accessToken) {
            $this->_fbeHelper->log('No access token available, skipping onboarding to onsite checkout');
            return $this;
        }

        if (!$pageId) {
            $this->_fbeHelper->log('No FB page ID available, skipping onboarding to onsite checkout');
            return $this;
        }

        // save page ID
        $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $pageId);
        $this->_fbeHelper->log('Page ID saved on instance --- '. $pageId);

        // retrieve page access token
        $pageAccessToken = $this->graphApiAdapter->getPageAccessToken($accessToken, $pageId);
        if (!$pageAccessToken) {
            throw new LocalizedException(__('Cannot retrieve page access token'));
        }
        // save page access token
        $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ACCESS_TOKEN, $pageAccessToken);

        // retrieve commerce account ID
        $commerceAccountId = $this->graphApiAdapter->getPageMerchantSettingsId($pageAccessToken, $pageId);
        if (!$commerceAccountId) {
            // commerce account may not be created at this point
            return $this;
        }

        // save commerce account ID
        $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $commerceAccountId);

        // enable API integration
        $this->graphApiAdapter->associateMerchantSettingsWithApp($commerceAccountId, $accessToken);

        return $this;
    }
}