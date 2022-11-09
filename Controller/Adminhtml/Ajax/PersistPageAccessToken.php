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

class PersistPageAccessToken extends AbstractAjax
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
        $accessToken = $this->getRequest()->getParam('accessToken');
        if ($accessToken) {
            $pageToken = $this->graphApiAdapter->getPageTokenFromUserToken($accessToken);

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $pageToken)
                ->cleanCache();

            $commerceAccountId = $this->systemConfig->getCommerceAccountId();

            if (!$commerceAccountId) {
                $commerceAccountId = $this->graphApiAdapter->getPageMerchantSettingsId($pageToken);
                if (!$commerceAccountId) {
                    $response['success'] = false;
                    $response['message'] = __('Cannot fetch commerce account ID');
                    return $response;
                }
                $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $commerceAccountId);
            }

            // @todo we're missing permission for Commerce API for now, so just return
            $response['success'] = true;
            $response['access_token'] = $pageToken;
            return $response;

            $commerceAccountData = $this->graphApiAdapter->getCommerceAccountData($commerceAccountId, $pageToken);

            $pageId = $commerceAccountData['page_id'];
            $catalogId = $commerceAccountData['catalog_id'];

            if (!$pageId || !$catalogId) {
                $response['success'] = false;
                $response['message'] = __('Error persisting page and catalog ID');
                return $response;
            }

            $this->graphApiAdapter->associateMerchantSettingsWithApp($commerceAccountId, $pageToken);

            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $pageId)
                ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $catalogId)
                ->cleanCache();

            $response['success'] = true;
            $response['access_token'] = $pageToken;
        }
        return $response;
    }
}
