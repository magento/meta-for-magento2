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

namespace Meta\BusinessExtension\Controller\Adminhtml\Setup;

use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
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
            ->cleanCache();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/system_config/edit/section/facebook_business_extension');
        $this->messageManager->addSuccessMessage('Successfully registered FB merchant settings');
        return $resultRedirect;
    }
}
