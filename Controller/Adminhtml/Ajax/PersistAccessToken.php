<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class PersistAccessToken extends AbstractAjax
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

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
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
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
                ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE, SystemConfig::ONBOARDING_STATE_COMPLETED, $storeId)
                ->cleanCache();

            $response['success'] = true;
            $response['access_token'] = $accessToken;
        }
        return $response;
    }
}
