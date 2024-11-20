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
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        $storeIdFromParam = $this->getRequest()->getParam('store');
        return $this->systemConfig->castStoreIdAsInt($storeIdFromParam) ??
            $this->systemConfig->getDefaultStoreId();
    }

    /**
     * Retrieve Pixel ID
     *
     * @return string
     */
    public function getPixelId()
    {
        return $this->systemConfig->getPixelId($this->getStoreId());
    }

    /**
     * Get Pixel Automatic Matching status
     *
     * @return string
     */
    public function getAutomaticMatchingStatus(): string
    {
        $settingsAsString = $this->systemConfig->getPixelAamSettings($this->getStoreId());
        if ($settingsAsString) {
            $settingsAsArray = json_decode($settingsAsString, true);
            if ($settingsAsArray && isset($settingsAsArray['enableAutomaticMatching'])) {
                return $settingsAsArray['enableAutomaticMatching'] ? __('Enabled') : __('Disabled');
            }
        }
        return __('N/A');
    }

    /**
     * Get AAM help center article link
     *
     * @return string
     */
    public function getAutomaticMatchingHelpCenterArticleLink(): string
    {
        return 'https://www.facebook.com/business/help/611774685654668';
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
    public function isFBEInstalled(): bool
    {
        return $this->systemConfig->isFBEInstalled($this->getStoreId());
    }

    /**
     * Retrieve whether currently in Debug Mode
     *
     * @return bool
     */
    public function isDebugMode()
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

    /**
     * Should show store level configs in the extension config page
     *
     * @return bool
     */
    public function shouldShowStoreLevelConfig(): bool
    {
        // Single store mode will always see the default store
        if ($this->systemConfig->isSingleStoreMode()) {
            return true;
        } else {
            // The store ID is specified in the path param, show store level config
            $storeIdFromParam = $this->getRequest()->getParam('store');
            return $storeIdFromParam !== null;
        }
    }
}
