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

use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * @SuppressWarnings(PHPMD)
 */
class PersistAccessToken extends AbstractAjax
{
    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

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
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->graphApiAdapter = $graphApiAdapter;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function executeForJson()
    {
        $response = [];
        $accessToken = $this->getRequest()->getParam('access_token');
        $storeId = $this->getRequest()->getParam('store');
        if ($accessToken) {

            // @todo Implement OBO

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $accessToken, $storeId)
                ->cleanCache();

            $pageId = $this->graphApiAdapter->getPageIdFromUserToken($accessToken);
            if (!$pageId) {
                $response['success'] = false;
                $response['message'] = __('Cannot fetch page ID');
                return $response;
            }

            $commerceAccountId = $this->systemConfig->getCommerceAccountId($storeId);

            if (!$commerceAccountId) {
                $commerceAccountId = $this->graphApiAdapter->getPageMerchantSettingsId($accessToken, $pageId);
                if (!$commerceAccountId) {
                    $response['success'] = false;
                    $response['message'] = __('Cannot fetch commerce account ID');
                    return $response;
                }
                $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $commerceAccountId, $storeId);
            }

            $commerceAccountData = $this->graphApiAdapter->getCommerceAccountData($commerceAccountId, $accessToken);

            $catalogId = $commerceAccountData['catalog_id'];

            if (!$catalogId) {
                $response['success'] = false;
                $response['message'] = __('Cannot fetch catalog ID');
                return $response;
            }

            $this->graphApiAdapter->associateMerchantSettingsWithApp($commerceAccountId, $accessToken);

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $pageId, $storeId)
                ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $catalogId, $storeId)
                ->cleanCache();

            $response['success'] = true;
            $response['access_token'] = $accessToken;
        }
        return $response;
    }
}
