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

namespace Facebook\BusinessExtension\Block\Adminhtml\Order\View\Tab;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use Magento\Sales\Model\Order;

class Facebook extends Template implements TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'order/view/tab/facebook.phtml';

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var Admin
     */
    private $adminHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param SystemConfig $systemConfig
     * @param Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SystemConfig $systemConfig,
        Admin $adminHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->systemConfig = $systemConfig;
        $this->adminHelper = $adminHelper;
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Facebook');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Facebook');
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('sales/*/facebook', ['_current' => true]);
    }

    /**
     * @return string|null
     */
    public function getFacebookOrderId()
    {
        return $this->getOrder()->getExtensionAttributes()->getFacebookOrderId();
    }

    /**
     * @return bool
     */
    public function isFacebookOrder()
    {
        return $this->getFacebookOrderId() !== null;
    }

    /**
     * @return string
     */
    public function getOrderFacebookUrl()
    {
        return sprintf(
            'https://www.facebook.com/commerce_manager/%s/orders/%s/',
            $this->systemConfig->getCommerceAccountId($this->getOrder()->getStoreId()),
            $this->getFacebookOrderId()
        );
    }

    /**
     * @return bool
     */
    public function isEmailRemarketingAllowedForOrder()
    {
        return $this->getOrder()->getExtensionAttributes()->getEmailRemarketingOption();
    }
}
