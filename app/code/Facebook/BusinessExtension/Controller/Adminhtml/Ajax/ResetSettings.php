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

class ResetSettings extends AbstractAjax
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

    /**
     * @return array
     */
    public function executeForJson()
    {
        $storeId = $this->getRequest()->getParam('store');
        $defaultStoreId = $this->_fbeHelper->getStore()->getId();
        $this->systemConfig->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $defaultStoreId)
            ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE, 0, $storeId)
            ->cleanCache();

        $successMessage = __('Successfully removed core configuration data.');
        $this->messageManager->addSuccessMessage($successMessage);

        return [
            'success' => true,
            'message' => $successMessage,
        ];
    }
}
