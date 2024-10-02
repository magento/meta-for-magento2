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
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\CatalogConfigUpdateHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Psr\Log\LoggerInterface;
use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MBEInstalls
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
     * @var CatalogConfigUpdateHelper
     */
    private CatalogConfigUpdateHelper $catalogConfigUpdateHelper;
    /**
     * @var ApiKeyService
     */
    private ApiKeyService $apiKeyService;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdobeCloudConfigInterface
     */
    private AdobeCloudConfigInterface $adobeConfig;

    /**
     * Construct
     *
     * @param FBEHelper                 $fbeHelper
     * @param SystemConfig              $systemConfig
     * @param GraphAPIAdapter           $graphApiAdapter
     * @param FacebookInstalledFeature  $installedFeatureResource
     * @param CatalogConfigUpdateHelper $catalogConfigUpdateHelper
     * @param ApiKeyService             $apiKeyService
     * @param StoreManagerInterface     $storeManager
     * @param LoggerInterface           $logger
     * @param AdobeCloudConfigInterface $adobeConfig
     */
    public function __construct(
        FBEHelper                 $fbeHelper,
        SystemConfig              $systemConfig,
        GraphAPIAdapter           $graphApiAdapter,
        FacebookInstalledFeature  $installedFeatureResource,
        CatalogConfigUpdateHelper $catalogConfigUpdateHelper,
        ApiKeyService             $apiKeyService,
        StoreManagerInterface     $storeManager,
        LoggerInterface           $logger,
        AdobeCloudConfigInterface $adobeConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->installedFeatureResource = $installedFeatureResource;
        $this->catalogConfigUpdateHelper = $catalogConfigUpdateHelper;
        $this->apiKeyService = $apiKeyService;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->adobeConfig = $adobeConfig;
    }

    /**
     * Process fbe_installs response
     *
     * @param  array $response
     * @param  int   $storeId
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
        $catalogId = $data['catalog_id'] ?? '';
        $pixelId = $data['pixel_id'] ?? '';
        $commercePartnerIntegrationId = $data['commerce_partner_integration_id'] ?? '';

        // we will update catalog config if catalog has been updated in Meta
        $this->catalogConfigUpdateHelper->updateCatalogConfiguration(
            (int)$storeId,
            $catalogId,
            $commercePartnerIntegrationId,
            $pixelId,
            false
        );

        $this->savePixelId($pixelId, $storeId);
        $this->saveProfiles($data['profiles'] ?? '', $storeId);
        $this->savePages($data['pages'] ?? '', $storeId);
        $this->saveCatalogId($catalogId, $storeId);
        $this->saveCommercePartnerIntegrationId($commercePartnerIntegrationId, $storeId);
        $this->saveMerchantSettingsId($data['commerce_merchant_settings_id'] ?? '', $storeId);
        $this->saveInstalledFeatures($data['installed_features'] ?? '', $storeId);
        $this->setInstalledFlag($storeId);
        $this->systemConfig->cleanCache();
        return true;
    }

    /**
     * Save pixelId and update AAMSettings
     *
     * @param array $pixelId
     * @param int   $storeId
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
     * @param int   $storeId
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
     * @param  array $pages
     * @param  int   $storeId
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
     * @param  int $commercePartnerIntegrationId
     * @param  int $storeId
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
            $this->fbeHelper->log(
                "Saved fbe_installs commerce_partner_integration_id ---" .
                "{$commercePartnerIntegrationId} for storeID: {$storeId}"
            );
        }
        return $this;
    }

    /**
     * Save commerce merchant settings id
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
     * @param int   $storeId
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

    /**
     * Update install flag to true and save
     *
     * @param  int $storeId
     */
    public function setInstalledFlag($storeId)
    {
        // set installed to true
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED,
            true,
            $storeId,
        );
    }

    /**
     * Update MBE settings through the 'fbe_installs' API
     *
     * @param  int $storeId
     * @return void
     */
    public function updateMBESettings($storeId)
    {
        $accessToken = $this->systemConfig->getAccessToken($storeId);
        $businessId = $this->systemConfig->getExternalBusinessId($storeId);
        if (!$accessToken || !$businessId) {
            $this->fbeHelper->log("AccessToken or BusinessID not found for storeID: {$storeId}");
            return;
        }
        $response = $this->graphApiAdapter->getFBEInstalls($accessToken, $businessId);
        $this->save($response['data'], $storeId);
        $this->fbeHelper->log("Updated MBE Settings for storeId: {$storeId}");
    }

    /**
     * Delete MBE settings through the 'fbe_installs' API
     *
     * @param  int $storeId
     * @return void
     */
    public function deleteMBESettings($storeId)
    {
        $accessToken = $this->systemConfig->getAccessToken($storeId);
        $businessId = $this->systemConfig->getExternalBusinessId($storeId);
        if (!$accessToken || !$businessId) {
            $this->fbeHelper->log("AccessToken or BusinessID not found for storeID: {$storeId}");
            return;
        }
        $this->graphApiAdapter->deleteFBEInstalls($accessToken, $businessId);
        $this->fbeHelper->log("Delete MBE Settings for storeId: {$storeId}");
    }

    /**
     * Call Repair CommercePartnerIntegration endpoint
     *
     * Keep Meta side CommercePartnerIntegration updated with latest info from Magento
     *
     * @param  int $storeId
     * @return bool
     * @throws \Exception
     */
    public function repairCommercePartnerIntegration($storeId): bool
    {
        try {
            $accessToken = $this->systemConfig->getAccessToken($storeId);
            $externalBusinessId = $this->systemConfig->getExternalBusinessId($storeId);
            $customToken = $this->apiKeyService->getCustomApiKey();
            $domain = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            $seller_platform_type = $this->adobeConfig->getCommercePartnerSellerPlatformType();
            $extensionVersion = $this->systemConfig->getModuleVersion();

            $response = $this->graphApiAdapter->repairCommercePartnerIntegration(
                $externalBusinessId,
                $domain,
                $customToken,
                $accessToken,
                $seller_platform_type,
                $extensionVersion
            );
            if ($response['success'] === true) {
                $integrationId = $response['id'];
                $existingIntegrationId = $this->systemConfig->getCommercePartnerIntegrationId($storeId);
                if ($existingIntegrationId !== null && $existingIntegrationId === $integrationId) {
                    return true;
                }

                // For some legacy sellers the Integration ID was obtained from CMS.
                // The method should be the ground truth.
                // Updating the ID and notify Meta.
                if ($existingIntegrationId !== $integrationId) {
                    $context = [
                        'store_id' => $storeId,
                        'event' => 'inconsistent_cpi',
                    ];
                    $e = new \Exception(
                        "Commerce Partner Integration ID inconsistent between Meta and Magento. 
                    Existing ID: $existingIntegrationId and New ID: $integrationId"
                    );
                    $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
                }
                $this->saveCommercePartnerIntegrationId($integrationId, $storeId);
                $this->systemConfig->cleanCache();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            $this->logger->error("Error trying to repair Meta Commerce Partner Integration");
            throw $ex;
        }
    }
}
