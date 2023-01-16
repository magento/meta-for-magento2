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

class PersistPageAccessToken extends AbstractAjax
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeForJson()
    {
        $response = [];
        $accessToken = $this->getRequest()->getParam('accessToken');
        $storeId = $this->getRequest()->getParam('storeId');
        if ($accessToken) {
            $pageToken = $this->graphApiAdapter->getPageTokenFromUserToken($accessToken);

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $pageToken, $storeId)
                ->cleanCache();

            $commerceAccountId = $this->systemConfig->getCommerceAccountId();

            if (!$commerceAccountId) {
                $commerceAccountId = $this->graphApiAdapter->getPageMerchantSettingsId($pageToken);
                if (!$commerceAccountId) {
                    $response['success'] = false;
                    $response['message'] = __('Cannot fetch commerce account ID');
                    return $response;
                }
                $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $commerceAccountId, $storeId);
            }

            // @todo we're missing permission for Commerce API for now, so just return
            $response['success'] = true;
            $response['access_token'] = $pageToken;
            return $response;
        }
        return $response;
    }
}
