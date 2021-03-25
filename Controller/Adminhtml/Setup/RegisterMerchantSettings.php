<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Setup;

use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class RegisterMerchantSettings extends Action
{
    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param GraphAPIAdapter $graphApiAdapter
     * @param SystemConfig $systemConfig
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Context $context,
        GraphAPIAdapter $graphApiAdapter,
        SystemConfig $systemConfig,
        ResultFactory $resultFactory)
    {
        parent::__construct($context);
        $this->graphApiAdapter = $graphApiAdapter;
        $this->systemConfig = $systemConfig;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Load the page defined in view/adminhtml/layout/fbeadmin_setup_index.xml
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        $merchantSettingsId = $this->getRequest()->getParam('cms_id');
        if (!$merchantSettingsId) {
            $this->messageManager->addErrorMessage('Cannot register FB commerce account. Missing merchant settings ID.');
        }
        $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $merchantSettingsId)
            ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE, SystemConfig::ONBOARDING_STATE_IN_PROGRESS_NEW_SHOP)
            ->cleanCache();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/system_config/edit/section/facebook_business_extension');
        $this->messageManager->addSuccessMessage('Successfully registered FB merchant settings');
        return $resultRedirect;
    }
}
