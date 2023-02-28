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

namespace Meta\Promotions\Block\Adminhtml\System\Config;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class PushPromotions extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Meta_Promotions::system/config/push_promotions.phtml';

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
     * Get ajax URL
     *
     * @todo move to helper
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('fbeadmin/ajax/promotionFeedUpload', ['store' => $this->getRequest()->getParam('store')]);
    }

    /**
     * Get element html
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get button html
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        /** @var Button $button */
        $button = $this->getLayout()->createBlock(Button::class);
        return $button->setData(['id' => 'fb_push_promotions_btn', 'label' => __('Push Promotions to Facebook')])
            ->toHtml();
    }

    /**
     * Get store id
     *
     * @return mixed
     */
    protected function getStoreId()
    {
        return $this->getRequest()->getParam('store');
    }

    /**
     * Get commerce account id
     *
     * @return string
     */
    public function getCommerceAccountId()
    {
        return $this->systemConfig->getCommerceAccountId($this->getStoreId());
    }

    /**
     * Get promotion urls
     *
     * @return string
     */
    public function getCommerceManagerPromotionsUrl()
    {
        return $this->systemConfig->getPromotionsUrl($this->getStoreId());
    }
}
