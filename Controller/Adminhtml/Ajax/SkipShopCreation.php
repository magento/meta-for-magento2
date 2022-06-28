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

class SkipShopCreation extends AbstractAjax
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
        GraphAPIAdapter $graphApiAdapter
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
    }

    public function executeForJson()
    {
        $storeId = $this->getRequest()->getParam('store');
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE,
            SystemConfig::ONBOARDING_STATE_IN_PROGRESS_EXISTING_SHOP,
            $storeId
        )
            ->cleanCache();

        $successMessage = __('Successfully updated onboarding state.');

        return [
            'success' => true,
            'message' => $successMessage,
        ];
    }
}
