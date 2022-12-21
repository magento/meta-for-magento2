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
        return __('Meta Business Extension: Complete the setup by configuring <a href="%1">Shipping Methods Mapping</a>.', $url);
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
