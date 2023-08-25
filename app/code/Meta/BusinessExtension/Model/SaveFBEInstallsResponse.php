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

namespace Meta\BusinessExtension\Model;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class SaveFBEInstallsResponse
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
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var FacebookInstalledFeature
     */
    private $installedFeatureResource;

    /**
     * Construct
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FacebookInstalledFeature $installedFeatureResource
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        FacebookInstalledFeature $installedFeatureResource
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->installedFeatureResource = $installedFeatureResource;
    }

    /**
     * Process fbe_installs response
     *
     * @param array $response
     * @param int $storeId
     * @return bool
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function save($response, $storeId)
    {
        if (!is_array($response) || empty($response)) {
            $this->fbeHelper->log('Skipping FBEInstalls save. Response format is incorrect.');
            return false;
        }
        $data = $response[0];

        $this->savePixelId($data['pixel_id'] ?? '', $storeId);
        $this->saveProfiles($data['profiles'] ?? '', $storeId);
        $this->savePages($data['pages'] ?? '', $storeId);
        $this->saveCatalogId($data['catalog_id'] ?? '', $storeId);
        $this->saveCommercePartnerIntegrationId(
            $data['commerce_partner_integration_id'] ?? '',
            $storeId
        );
        $this->saveMerchantSettingsId($data['commerce_merchant_settings_id'] ?? '', $storeId);
        $this->saveInstalledFeatures($data['installed_features'] ?? '', $storeId);
        $this->systemConfig->cleanCache();
        return true;
    }

    /**
     * Save pixelId and update AAMSettings
     *
     * @param array $pixelId
     * @param int $storeId
     */
    private function savePixelId($pixelId, $storeId)
    {
        if ($pixelId && $this->fbeHelper->isValidFBID($pixelId)) {
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID,
                $pixelId,
                $storeId
            );
            $this->fbeHelper->fetchAndSaveAAMSettings($pixelId, $storeId);
            $this->fbeHelper->log("Saved fbe_installs pixel_id --- {$pixelId} for storeId: {$storeId}");
        } else {
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID,
                '',
                $storeId
            );
        }
    }

    /**
     * Save profiles
     *
     * @param array $profiles
     * @param int $storeId
     */
    private function saveProfiles($profiles, $storeId)
    {
        if ($profiles) {
            $profiles = json_encode($profiles);
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES,
                $profiles,
                $storeId
            );
            $this->fbeHelper->log("Saved fbe_installs profiles --- {$profiles} for storeID: {$storeId}");
        }
    }

    /**
     * Save pages
     *
     * @param array $pages
     * @param int $storeId
     * @throws GuzzleException
     * @throws LocalizedException
     */
    private function savePages($pages, $storeId)
    {
        if (empty($pages)) {
            return;
        }

        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID,
            $pages[0],
            $storeId
        );

        $accessToken = $this->systemConfig->getAccessToken($storeId, ScopeInterface::SCOPE_STORE);
        $pageAccessToken = $this->graphApiAdapter->getPageAccessToken($accessToken, $pages[0]);
        if (!$pageAccessToken) {
            throw new LocalizedException(__('Cannot retrieve page access token'));
        }

        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ACCESS_TOKEN,
            $pageAccessToken,
            $storeId
        );
        $this->fbeHelper->log("Saved fbe_installs page_id --- {$pages[0]} for storeID: {$storeId}");
    }

    /**
     * Save catalog id
     *
     * @param int $catalogId
     * @param int $storeId
     */
    private function saveCatalogId($catalogId, $storeId)
    {
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID,
            $catalogId,
            $storeId
        );
        $this->fbeHelper->log("Saved fbe_installs catalog_id --- {$catalogId} for storeID: {$storeId}");
    }

    /**
     * Save commerce partner integration id
     *
     * @param int $commercePartnerIntegrationId
     * @param int $storeId
     * @return $this
     */
    public function saveCommercePartnerIntegrationId($commercePartnerIntegrationId, $storeId)
    {
        if ($commercePartnerIntegrationId) {
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_PARTNER_INTEGRATION_ID,
                $commercePartnerIntegrationId,
                $storeId
            );
            $this->fbeHelper->log("Saved fbe_installs commerce_partner_integration_id ---" .
             "{$commercePartnerIntegrationId} for storeID: {$storeId}");
        }
        return $this;
    }

    /**
     * Save commerce merchange settings id
     *
     * @param int $merchantSettingsId
     * @param int $storeId
     */
    private function saveMerchantSettingsId($merchantSettingsId, $storeId)
    {
        if ($merchantSettingsId) {
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID,
                $merchantSettingsId,
                $storeId
            );
            $this->fbeHelper->log(
                "Saved fbe_installs merchant settings ID --- {$merchantSettingsId} for storeID: {$storeId}"
            );
        }
    }

    /**
     * Save installed features
     *
     * @param array $data
     * @param int $storeId
     */
    private function saveInstalledFeatures($data, $storeId)
    {
        $this->installedFeatureResource->deleteAll($storeId);
        if (empty($data)) {
            return;
        }
        $this->installedFeatureResource->saveResponseData($data, $storeId);
        $this->fbeHelper->log("Saved fbe_installs 'installed_features' for storeId: {$storeId}");
    }
}
