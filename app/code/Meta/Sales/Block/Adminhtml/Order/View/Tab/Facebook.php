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

namespace Meta\Sales\Block\Adminhtml\Order\View\Tab;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Sales\Helper\Admin;
use Magento\Sales\Model\Order;

class Facebook extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Meta_Sales::order/view/tab/facebook.phtml';

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
     * @param SystemConfig $systemConfig
     * @param Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        Admin $adminHelper,
        array $data = []
    ) {
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
        return $this->getLayout()->getBlock('order_tab_info')->getOrder();
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return $this->systemConfig->isOnsiteCheckoutEnabled();
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Meta');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Meta');
    }

    /**
     * Get facebook order id
     *
     * @return string|null
     */
    public function getFacebookOrderId()
    {
        return $this->getOrder()->getExtensionAttributes()->getFacebookOrderId();
    }

    /**
     * Check if order is facebbok order
     *
     * @return bool
     */
    public function isFacebookOrder()
    {
        return $this->getFacebookOrderId() !== null;
    }

    /**
     * Get order's facebook url
     *
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
     * Check if email remarketing is allowed for order
     *
     * @return bool
     */
    public function isEmailRemarketingAllowedForOrder()
    {
        return $this->getOrder()->getExtensionAttributes()->getEmailRemarketingOption();
    }
}
