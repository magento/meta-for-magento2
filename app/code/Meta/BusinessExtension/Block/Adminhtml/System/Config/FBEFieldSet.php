<?php

declare(strict_types=1);

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

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class FBEFieldSet extends Fieldset
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor
     *
     * @param  Context               $context
     * @param  Session               $authSession
     * @param  Js                    $jsHelper
     * @param  SystemConfig          $systemConfig
     * @param  StoreManagerInterface $storeManager
     * @param  array                 $data
     * @return string
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        SystemConfig $systemConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->storeManager = $storeManager;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Render element for MBE catalog and conversion admin configuration fields
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $storeId = $this->getRequest()->getParam('store');
        $storeId = $storeId ?: $this->storeManager->getDefaultStoreView()->getId();
        $group = $element->getDataByPath('group/group');

        switch ($group) {
        case 'catalog':
            $showSection = $this->systemConfig->isFBECatalogInstalled($storeId);
            break;
        case 'conversion':
            $showSection = $this->systemConfig->isFBEPixelInstalled($storeId);
            break;
        case 'ads':
            $showSection = $this->systemConfig->isFBEAdsInstalled($storeId);
            break;
        case 'fb_shop':
            $showSection = $this->systemConfig->isFBEShopInstalled($storeId);
            break;
        default:
            $showSection = false;
        }
        
        if ($showSection) {
            return parent::render($element);
        }

        return '<div style="display: none;">' . parent::render($element) . '</div>';
    }
}
