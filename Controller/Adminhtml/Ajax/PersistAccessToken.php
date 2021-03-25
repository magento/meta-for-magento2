<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
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

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter)
    {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
    }

    public function executeForJson()
    {
        $response = [];
        $accessToken = $this->getRequest()->getParam('access_token');
        if ($accessToken) {

            // @todo Implement OBO

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $accessToken)
                ->cleanCache();

            $commerceAccountId = $this->systemConfig->getCommerceAccountId();

            if (!$commerceAccountId) {
                $commerceAccountId = $this->graphApiAdapter->getPageMerchantSettingsId($accessToken);
                if (!$commerceAccountId) {
                    $response['success'] = false;
                    $response['message'] = __('Cannot fetch commerce account ID');
                    return $response;
                }
                $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $commerceAccountId);
            }

            $commerceAccountData = $this->graphApiAdapter->getCommerceAccountData($commerceAccountId, $accessToken);

            $pageId = $commerceAccountData['page_id'];
            $catalogId = $commerceAccountData['catalog_id'];

            if (!$pageId || !$catalogId) {
                $response['success'] = false;
                $response['message'] = __('Error persisting page and catalog ID');
                return $response;
            }

            $this->graphApiAdapter->associateMerchantSettingsWithApp($commerceAccountId, $accessToken);

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $pageId)
                ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $catalogId)
                ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE, SystemConfig::ONBOARDING_STATE_COMPLETED)
                ->cleanCache();

            $response['success'] = true;
            $response['access_token'] = $accessToken;
        }
        return $response;
    }
}
