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

namespace Meta\BusinessExtension\Model\System\Message;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class Notification implements MessageInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

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
     * @inheritDoc
     */
    public function getIdentity()
    {
        return 'facebook_notification';
    }

    /**
     * @inheritDoc
     */
    public function isDisplayed()
    {
        $storeId = $this->request->getParam('store');
        if (!($this->systemConfig->isActiveExtension($storeId)
            && $this->systemConfig->getAccessToken($storeId)
            && $this->systemConfig->isOnsiteCheckoutEnabled($storeId))) {
            return false;
        }

        return !$this->isShippingMappingConfigured($storeId);
    }

    /**
     * @inheritDoc
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/facebook_business_extension');
        $text = 'Meta Business Extension: Complete the setup by configuring <a href="%1">Shipping Methods Mapping</a>.';
        return __($text, $url);
    }

    /**
     * @inheritDoc
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * Check if shipping mapping configured.
     *
     * @param int $storeId
     * @return bool
     */
    private function isShippingMappingConfigured($storeId)
    {
        foreach ($this->systemConfig->getShippingMethodsMap($storeId) as $value) {
            if ($value !== null) {
                return true;
            }
        }
        return false;
    }
}
