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

namespace Meta\BusinessExtension\Block\Adminhtml;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Template;

/**
 * @api
 */
class Setup extends Template
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        array $data = []
    ) {
        $this->fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
        $this->systemConfig = $systemConfig;
    }

    /**
     * @return mixed
     */
    public function getPixelAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbpixel');
    }

    /**
     * @return mixed
     */
    public function getAccessTokenAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbtoken');
    }

    /**
     * @return mixed
     */
    public function getProfilesAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbprofiles');
    }

    /**
     * @return mixed
     */
    public function getAAMSettingsRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbaamsettings');
    }

    /**
     * @return string|null
     */
    public function fetchPixelId()
    {
        return $this->systemConfig->getPixelId();
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExternalBusinessId()
    {
        return $this->fbeHelper->getFBEExternalBusinessId();
    }

    /**
     * @return mixed
     */
    public function fetchConfigurationAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/persistConfiguration');
    }

    /**
     * @return mixed
     */
    public function getDeleteAssetIdsAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbdeleteasset');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->fbeHelper->getStoreCurrencyCode();
    }

    /**
     * @return string
     */
    public function isFBEInstalled()
    {
        return $this->systemConfig->isFBEInstalled() ? 'true' : 'false';
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->systemConfig->getAppId();
    }
}
