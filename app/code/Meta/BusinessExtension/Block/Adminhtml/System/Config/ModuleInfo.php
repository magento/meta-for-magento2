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

namespace Meta\BusinessExtension\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;

class ModuleInfo extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Meta_BusinessExtension::system/config/module_info.phtml';

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ApiKeyService
     */
    private $apiKeyService;

    /**
     * @param Context       $context
     * @param SystemConfig  $systemConfig
     * @param ApiKeyService $apiKeyService
     * @param array         $data
     */
    public function __construct(
        Context       $context,
        SystemConfig  $systemConfig,
        ApiKeyService $apiKeyService,
        array         $data = []
    ) {
        $this->systemConfig = $systemConfig;
        $this->apiKeyService = $apiKeyService;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param                                         AbstractElement $element
     * @return                                        string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->systemConfig->getModuleVersion();
    }

    /**
     * Retrieve Store Id
     *
     * @return mixed
     */
    private function getStoreId()
    {
        return $this->getRequest()->getParam('store');
    }

    /**
     * Retrieve Commerce Account ID
     *
     * @return string
     */
    public function getCommerceAccountId()
    {
        return $this->systemConfig->getCommerceAccountId($this->getStoreId());
    }

    /**
     * Retrieve Page Id
     *
     * @return string
     */
    public function getPageId()
    {
        return $this->systemConfig->getPageId($this->getStoreId());
    }

    /**
     * Retrieve Catalog Id
     *
     * @return string
     */
    public function getCatalogId()
    {
        return $this->systemConfig->getCatalogId($this->getStoreId());
    }

    /**
     * Retrieve Commerce Manager Url
     *
     * @return string
     */
    public function getCommerceManagerUrl()
    {
        return $this->systemConfig->getCommerceManagerUrl($this->getStoreId());
    }

    /**
     * Retrieve Catalog Manager Url
     *
     * @return string
     */
    public function getCatalogManagerUrl()
    {
        return $this->systemConfig->getCatalogManagerUrl($this->getStoreId());
    }

    /**
     * Retrieve Support Url
     *
     * @return string
     */
    public function getSupportUrl()
    {
        return $this->systemConfig->getSupportUrl($this->getStoreId());
    }

    /**
     * Retrieve Custom API Key
     *
     * @return string
     */
    public function getAPIToken()
    {
        return $this->apiKeyService->getCustomApiKey();
    }

    /**
     * Retrieve FBE installation status for this store
     *
     * @return bool
     */
    public function getIsFBEInstalled()
    {
        return $this->systemConfig->isFBEInstalled($this->getStoreId());
    }

    /**
     * Retrieve whether currently in Debug Mode
     *
     * @return bool
     */
    public function getIsDebugMode()
    {
        return $this->systemConfig->isDebugMode();
    }

    /**
     * Retrieve the Commerce Partner Integration ID
     *
     * @return string
     */
    public function getCommercePartnerIntegrationId()
    {
        return $this->systemConfig->getCommercePartnerIntegrationId($this->getStoreId());
    }

    /**
     * Retrieve the Commerce Partner External Business ID
     *
     * @return string
     */
    public function getExternalBusinessID()
    {
        return $this->systemConfig->getExternalBusinessId($this->getStoreId());
    }
}
