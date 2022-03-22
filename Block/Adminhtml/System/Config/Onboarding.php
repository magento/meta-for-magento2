<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml\System\Config;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Onboarding extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Facebook_BusinessExtension::system/config/onboarding.phtml';

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @param Context $context
     * @param SystemConfig $systemConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        array $data = []
    ) {
        $this->systemConfig = $systemConfig;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->systemConfig->getAppId();
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
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    protected function getRedirectUrl()
    {
        return $this->getUrl('fbeadmin/setup/registerMerchantSettings');
    }

    /**
     * @return mixed
     */
    public function getRegisteredCommerceAccountId()
    {
        return $this->systemConfig->getCommerceAccountId();
    }

    /**
     * @return mixed
     */
    public function getRegisteredAccessToken()
    {
        return $this->systemConfig->getAccessToken();
    }

    /**
     * @return mixed
     */
    public function getOnboardingState()
    {
        return $this->systemConfig->getOnboardingState();
    }

    /**
     * @return string
     */
    public function getCommerceManagerOnboardingUrl($isTestMode = false)
    {
        $isTestMode = $isTestMode ? 'true' : 'false';
        return 'https://www.facebook.com/commerce_manager/onboarding?is_test_mode=' . $isTestMode . '&app_id=' . $this->getAppId()
            . '&redirect_url=' . $this->getRedirectUrl();
    }

    public function getPersistAccessTokenUrl()
    {
        return $this->getUrl('fbeadmin/ajax/persistAccessToken');
    }

    public function getResetSettingsUrl()
    {
        return $this->getUrl('fbeadmin/ajax/resetSettings');
    }

    public function getSkipShopCreationUrl()
    {
        return $this->getUrl('fbeadmin/ajax/skipShopCreation');
    }
}
