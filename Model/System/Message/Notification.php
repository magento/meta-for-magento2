<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\System\Message;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class Notification implements MessageInterface
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param SystemConfig $systemConfig
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     */
    public function __construct(
        SystemConfig $systemConfig,
        UrlInterface $urlBuilder,
        RequestInterface $request
    ) {
        $this->systemConfig = $systemConfig;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentity()
    {
        return 'facebook_notification';
    }

    /**
     * @param $storeId
     * @return bool
     */
    protected function isShippingMappingConfigured($storeId)
    {
        foreach ($this->systemConfig->getShippingMethodsMap($storeId) as $key => $value) {
            if ($value !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isDisplayed()
    {
        $storeId = $this->request->getParam('store');
        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->getAccessToken($storeId))) {
            return false;
        }

        return !$this->isShippingMappingConfigured($storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/facebook_business_extension');
        return __('Facebook Business Extension: Complete the setup by configuring <a href="%1">Shipping Methods Mapping</a>.', $url);
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
