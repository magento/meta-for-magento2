<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class ModuleInfo extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Facebook_BusinessExtension::system/config/module_info.phtml';

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
     * @param  AbstractElement $element
     * @return string
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
     * @return string
     */
    public function getCommerceAccountId()
    {
        return $this->systemConfig->getCommerceAccountId();
    }

    /**
     * @return string
     */
    public function getCatalogId()
    {
        return $this->systemConfig->getCatalogId();
    }

    /**
     * @return string
     */
    public function getCommerceManagerUrl()
    {
        return $this->systemConfig->getCommerceManagerUrl();
    }

    /**
     * @return string
     */
    public function getCatalogManagerUrl()
    {
        return $this->systemConfig->getCatalogManagerUrl();
    }

    /**
     * @return string
     */
    public function getSupportUrl()
    {
        return $this->systemConfig->getSupportUrl();
    }
}
