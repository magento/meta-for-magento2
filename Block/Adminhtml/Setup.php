<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml;

use Facebook\BusinessExtension\Helper\FBEHelper;

class Setup extends \Magento\Backend\Block\Template
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param FBEHelper $fbeHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        FBEHelper $fbeHelper,
        array $data = []
    ) {
        $this->fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
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
        return $this->fbeHelper->getConfigValue('fbpixel/id');
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
    public function getFeedPushAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbfeedpush');
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
        return $this->fbeHelper->isFBEInstalled();
    }
}
