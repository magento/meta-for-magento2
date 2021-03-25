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

class ResetSettings extends AbstractAjax
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
        $this->systemConfig->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID)
            ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE, SystemConfig::ONBOARDING_STATE_PENDING)
            ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE, 0)
            ->cleanCache();

        $successMessage = __('Successfully removed core configuration data.');
        $this->messageManager->addSuccessMessage($successMessage);

        return [
            'success' => true,
            'message' => $successMessage,
        ];
    }
}
