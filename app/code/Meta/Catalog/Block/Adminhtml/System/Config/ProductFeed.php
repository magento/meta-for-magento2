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

namespace Meta\Catalog\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductFeed extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Meta_Catalog::system/config/product_feed.phtml';

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
     * Get Ajax url
     *
     * @todo move to helper
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('fbeadmin/ajax/productFeedUpload', ['store' => $this->getRequest()->getParam('store')]);
    }

    /**
     * Get store name
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreNameForComment()
    {
        return $this->getRequest()->getParam('store')
            ? $this->_storeManager->getStore($this->getRequest()->getParam('store'))->getName()
            : $this->_storeManager->getDefaultStoreView()->getName();
    }

    /**
     * Get html
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
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        /** @var Button $button */
        $button = $this->getLayout()->createBlock(Button::class);
        return $button->setData(['id' => 'fb_feed_upload_btn', 'label' => __('Upload to Meta')])
            ->toHtml();
    }
}
