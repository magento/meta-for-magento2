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
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * Construct
     *
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
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Execute for json
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeForJson()
    {
        try {
            $storeId = $this->getRequest()->getParam('storeId');
            $accessToken = $this->getRequest()->getParam('accessToken');
            $externalBusinessId = $this->getRequest()->getParam('externalBusinessId');
            $catalogId = $this->getRequest()->getParam('catalogId');
            $pageId = $this->getRequest()->getParam('pageId');

            $this->saveExternalBusinessId($externalBusinessId, $storeId)
                ->saveCatalogId($catalogId, $storeId)
                ->saveInstalledFlag($storeId)
                ->completeOnsiteOnboarding($accessToken, $pageId, $storeId);

            $response['success'] = true;
            $response['message'] = 'Configuration successfully saved';
            return $response;
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->fbeHelper->logException($e);
            return $response;
        }
    }

    /**
     * Save catalog id
     *
     * @param int $catalogId
     * @param int $storeId
     * @return $this
     */
    public function saveCatalogId($catalogId, $storeId)
    {
        if ($catalogId) {
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID,
                $catalogId,
                $storeId
            );
            $this->fbeHelper->log('Catalog ID saved on instance --- '. $catalogId);
        }
        return $this;
    }

    /**
     * Save external business id
     *
     * @param int $externalBusinessId
     * @param int $storeId
     * @return $this
     */
    public function saveExternalBusinessId($externalBusinessId, $storeId)
    {
        if ($externalBusinessId) {
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID,
                $externalBusinessId,
                $storeId
            );
            $this->fbeHelper->log('External business ID saved on instance --- '. $externalBusinessId);
        }
        return $this;
    }

    /**
     * Update install flag to true and save
     *
     * @param int $storeId
     * @return $this
     */
    public function saveInstalledFlag($storeId)
    {
         // set installed to true
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED,
            true,
            $storeId,
        );
        return $this;
    }

    /**
     * Complete onsite onboarding
     *
     * @param string $accessToken
     * @param int $pageId
     * @param int $storeId
     * @return $this
     * @throws LocalizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */

    public function completeOnsiteOnboarding($accessToken, $pageId, $storeId)
    {
        if (!$accessToken) {
            $this->fbeHelper->log('No access token available, skipping onboarding to onsite checkout');
            return $this;
        }

        if (!$pageId) {
            $this->fbeHelper->log('No FB page ID available, skipping onboarding to onsite checkout');
            return $this;
        }

        // save page ID
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID,
            $pageId,
            $storeId
        );
        $this->fbeHelper->log('Page ID saved on instance --- '. $pageId);

        // retrieve page access token
        $pageAccessToken = $this->graphApiAdapter->getPageAccessToken($accessToken, $pageId);
        if (!$pageAccessToken) {
            throw new LocalizedException(__('Cannot retrieve page access token'));
        }
        // save page access token
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ACCESS_TOKEN,
            $pageAccessToken,
            $storeId
        );

        // retrieve commerce account ID
        $commerceAccountId = $this->graphApiAdapter->getPageMerchantSettingsId($pageAccessToken, $pageId);
        if (!$commerceAccountId) {
            // commerce account may not be created at this point
            $this->fbeHelper->log('No commerce account available, skipping onboarding to onsite checkout');
            return $this;
        }

        // save commerce account ID
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID,
            $commerceAccountId,
            $storeId
        );

        // enable API integration
        $this->graphApiAdapter->associateMerchantSettingsWithApp($commerceAccountId, $accessToken);

        return $this;
    }
}
