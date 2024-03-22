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

namespace Meta\BusinessExtension\Helper;

use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class CommerceExtensionHelper
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var array
     */
    private array $resultCacheByStoreID = [];

    /**
     * FBEHelper constructor
     *
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     */
    public function __construct(
        SystemConfig    $systemConfig,
        GraphAPIAdapter $graphAPIAdapter
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
    }

    /**
     * The URL to load the iframe splash page for non-onboarded stores.
     *
     * @return string
     */
    public function getSplashPageURL()
    {
        if (!$this->systemConfig->isActiveExtension()) {
            return 'https://business.facebook.com/fbe-iframe-get-started/?';
        }

        $base_url = $this->systemConfig->getCommerceExtensionBaseURL();
        return $base_url . 'commerce_extension/splash/?';
    }

    /**
     * The expected origin for the Messages received from the FBE iframe/popup.
     *
     * @return string
     */
    public function getPopupOrigin()
    {
        if (!$this->systemConfig->isActiveExtension()) {
            return 'https://business.facebook.com';
        }

        return $this->systemConfig->getCommerceExtensionBaseURL();
    }

    /**
     * Whether to enable the new Commerce Extension UI
     *
     * @param int $storeId
     * @return bool
     */
    public function isCommerceExtensionEnabled($storeId)
    {
        $storeHasCommercePartnerIntegration = !!$this->systemConfig->getCommercePartnerIntegrationId($storeId);
        return $storeHasCommercePartnerIntegration || $this->systemConfig->isActiveExtension($storeId);
    }

    /**
     * Whether there is an error blocking usage of the Commerce Extension.
     *
     * @param int $storeId
     * @return bool
     */
    public function hasCommerceExtensionPermissionError($storeId)
    {
        $this->ensureCacheIsPopulated($storeId);
        return $this->resultCacheByStoreID[$storeId]['permission_exception'] != null;
    }

    /**
     * Get a URL to use to render the CommerceExtension IFrame for an onboarded Store.
     *
     * @param int $storeId
     * @return string
     */
    public function getCommerceExtensionIFrameURL($storeId)
    {
        $this->ensureCacheIsPopulated($storeId);
        return $this->resultCacheByStoreID[$storeId]['url'];
    }

    /**
     * And adds the iframe URL or fetch error to a private cache.
     *
     * @param int $storeId
     */
    private function ensureCacheIsPopulated($storeId)
    {
        if (array_key_exists($storeId, $this->resultCacheByStoreID)) {
            return;
        }

        try {
            $url = $this->graphAPIAdapter->getCommerceExtensionIFrameURL(
                $this->systemConfig->getExternalBusinessId($storeId),
                $this->systemConfig->getAccessToken($storeId),
            );
            $this->resultCacheByStoreID[$storeId] = ['permission_exception' => null, 'url' => $url];
        } catch (GuzzleException $ex) {
            if ($ex->getCode() !== 400) {
                throw $ex;
            }

            $this->resultCacheByStoreID[$storeId] = ['permission_exception' => $ex, 'url' => null];
        }
    }
}
