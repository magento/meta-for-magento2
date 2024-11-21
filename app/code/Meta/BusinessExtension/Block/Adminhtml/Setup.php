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

namespace Meta\BusinessExtension\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;
use Meta\BusinessExtension\Helper\CommerceExtensionHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @api
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Setup extends Template
{
    public const COUNTRY_CONFIG_PATH = 'general/country/default';
    public const TIMEZONE_CONFIG_PATH = 'general/locale/timezone';

    /**
     * @var ApiKeyService
     */
    private ApiKeyService $apiKeyService;
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var StoreRepositoryInterface
     */
    public StoreRepositoryInterface $storeRepo;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CommerceExtensionHelper
     */
    private CommerceExtensionHelper $commerceExtensionHelper;

    /**
     * @var AdobeCloudConfigInterface
     */
    private AdobeCloudConfigInterface $adobeConfig;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param StoreRepositoryInterface $storeRepo
     * @param StoreManagerInterface $storeManager
     * @param CommerceExtensionHelper $commerceExtensionHelper
     * @param ApiKeyService $apiKeyService
     * @param AdobeCloudConfigInterface $adobeConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context                   $context,
        RequestInterface          $request,
        FBEHelper                 $fbeHelper,
        SystemConfig              $systemConfig,
        StoreRepositoryInterface  $storeRepo,
        StoreManagerInterface     $storeManager,
        CommerceExtensionHelper   $commerceExtensionHelper,
        ApiKeyService             $apiKeyService,
        AdobeCloudConfigInterface $adobeConfig,
        ScopeConfigInterface      $scopeConfig,
        array                     $data = []
    ) {
        $this->fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
        $this->request = $request;
        $this->systemConfig = $systemConfig;
        $this->storeRepo = $storeRepo;
        $this->storeManager = $storeManager;
        $this->commerceExtensionHelper = $commerceExtensionHelper;
        $this->apiKeyService = $apiKeyService;
        $this->adobeConfig = $adobeConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * ID of the selected Store.
     *
     * @return int|null
     */
    public function getSelectedStoreId(): ?int
    {
        $stores = $this->getSelectableStores();
        if (empty($stores)) {
            return null;
        }

        // If there is a store matching query param, return it.
        $requestStoreId = $this->systemConfig->castStoreIdAsInt($this->request->getParam('store_id'));
        if ($requestStoreId !== null) {
            try {
                $this->storeRepo->getById($requestStoreId);
                return $requestStoreId;
            } catch (NoSuchEntityException $ex) {
                $this->fbeHelper->log("Store with requestStoreId $requestStoreId not found");
            }
        }

        // Missing or invalid query param, look for the default
        return $this->systemConfig->getDefaultStoreId();
    }

    /**
     * Get pixel ajax route
     *
     * @return mixed
     */
    public function getPixelAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbpixel');
    }

    /**
     * Get access token ajax route
     *
     * @return mixed
     */
    public function getAccessTokenAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbtoken');
    }

    /**
     * Get profiles ajax route
     *
     * @return mixed
     */
    public function getProfilesAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbprofiles');
    }

    /**
     * Get aam settings route
     *
     * @return mixed
     */
    public function getAAMSettingsRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbaamsettings');
    }

    /**
     * Fetch pixel id
     *
     * @param int $storeId
     * @return string|null
     */
    public function fetchPixelId($storeId)
    {
        return $this->systemConfig->getPixelId($storeId);
    }

    /**
     * Whether to enable the new Commerce Extension UI
     *
     * @return bool
     */
    public function isCommerceExtensionEnabled()
    {
        $storeId = $this->getSelectedStoreId();
        return $this->commerceExtensionHelper->isCommerceExtensionEnabled($storeId);
    }

    /**
     * The expected origin for the Messages received from the FBE iframe/popup.
     *
     * @return string
     */
    public function getPopupOrigin()
    {
        return $this->commerceExtensionHelper->getPopupOrigin();
    }

    /**
     * The URL to load the FBE iframe splash page for non-onboarded stores.
     *
     * @return string
     */
    public function getSplashPageURL()
    {
        return $this->commerceExtensionHelper->getSplashPageURL();
    }

    /**
     * Get external business id
     *
     * @param int $storeId
     * @return string|null
     */
    public function getExternalBusinessId($storeId)
    {
        $storedExternalId = $this->systemConfig->getExternalBusinessId($storeId);
        if ($storedExternalId) {
            return $storedExternalId;
        }
        if ($storeId === null) {
            $storeId = $this->getSelectedStoreId();
        }

        $this->fbeHelper->log("Store id---" . $storeId);
        $generatedExternalId = uniqid('fbe_magento_' . $storeId . '_');
        $this->systemConfig->saveExternalBusinessIdForStore($generatedExternalId, $storeId);
        return $generatedExternalId;
    }

    /**
     * Fetch configuration ajax route
     *
     * @return mixed
     */
    public function fetchConfigurationAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/persistConfiguration');
    }

    /**
     * Fetch configuration ajax route
     *
     * @return mixed
     */
    public function fetchPostFBEOnboardingSyncAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/postFBEOnboardingSync');
    }

    /**
     * Get delete asset ids ajax route
     *
     * @return mixed
     */
    public function getCleanCacheAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/cleanCache');
    }

    /**
     * Get the ajax route to report client errors.
     *
     * @return mixed
     */
    public function getReportClientErrorRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/reportClientError');
    }

    /**
     * Get Delete Asset IDs Ajax Route
     *
     * @return mixed
     */
    public function getDeleteAssetIdsAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbdeleteasset');
    }

    /**
     * Get currency code
     *
     * @return null|string
     */
    public function getCurrencyCode(): ?string
    {
        return $this->fbeHelper->getStoreCurrencyCode();
    }

    /**
     * Is fbe installed
     *
     * @param int $storeId
     * @return bool
     */
    public function isFBEInstalled($storeId)
    {
        return $this->systemConfig->isFBEInstalled($storeId);
    }

    /**
     * Get a URL to use to render the CommerceExtension IFrame for an onboarded Store.
     *
     * @param int $storeId
     * @return string
     */
    public function getCommerceExtensionIFrameURL($storeId)
    {
        return $this->commerceExtensionHelper->getCommerceExtensionIFrameURL($storeId);
    }

    /**
     * Get a URL to use to render the CommerceExtension IFrame for an onboarded Store.
     *
     * @param int $storeId
     * @return bool
     */
    public function hasCommerceExtensionIFramePermissionError(int $storeId): bool
    {
        return $this->commerceExtensionHelper->hasCommerceExtensionPermissionError($storeId);
    }

    /**
     * Get app id
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->systemConfig->getAppId();
    }

    /**
     * Get client token
     *
     * @return string
     */
    public function getClientToken()
    {
        return '52dcd04d6c7ed113121b5eb4be23b4a7';
    }

    /**
     * Get access token
     *
     * @return string
     */
    public function getAccessClientToken()
    {
        return $this->getAppId().'|'.$this->getClientToken();
    }

    /**
     * Get stores that are selectable (not Admin).
     *
     * @return StoreInterface[]
     * */
    public function getSelectableStores()
    {
        $stores = $this->storeRepo->getList();

        return array_filter(
            $stores,
            fn($key) => $key !== 'admin',
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Get fbe installs config url endpoint
     *
     * @return string
     */
    public function getFbeInstallsConfigUrl()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbeinstallsconfig');
    }

    /**
     * Get fbe installs save url endpoint
     *
     * @return string
     */
    public function getFbeInstallsSaveUrl()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbeinstallssave');
    }

    /**
     * Get store id from request paramater
     *
     * @return string
     */
    public function getStoreId()
    {
        return $this->getRequest()->getParam('store');
    }

    /**
     * Get fbe installs save url endpoint
     *
     * @return string
     */
    public function getInstalledFeaturesAjaxRouteUrl()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbinstalledfeatures');
    }

    /**
     * Get store id from request paramater
     *
     * @return string
     */
    public function getWebsiteId()
    {
        return $this->getRequest()->getParam('website');
    }

    /**
     * Get default store_id
     *
     * @return int
     */
    public function getDefaultStoreViewId()
    {
        return $this->fbeHelper->getStore()->getId();
    }

    /**
     * Call this method to check and generate the API key
     *
     * @return string
     */
    public function upsertApiKey()
    {
        return $this->apiKeyService->upsertApiKey();
    }

    /**
     * Call this method to get a string indicator on seller types (hosted by Adobe).
     *
     * @return string
     */
    public function getCommercePartnerSellerPlatformType(): string
    {
        return $this->adobeConfig->getCommercePartnerSellerPlatformType();
    }

    /**
     * Call this method to Get the existing Api key or generate and return it.
     *
     * @return string
     */
    public function getCustomApiKey(): string
    {
        return $this->apiKeyService->getCustomApiKey();
    }

    /**
     * Get repair CPI ajax route
     *
     * @return mixed
     */
    public function getRepairRepairCommercePartnerIntegrationAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/RepairCommercePartnerIntegration');
    }

    /**
     * Get MBE Update Installed Config ajax route
     *
     * @return string
     */
    public function getUpdateMBEConfigAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/MBEUpdateInstalledConfig');
    }

    /**
     * Get Store's Timezone
     *
     * @return string
     */
    public function getStoreTimezone(): string
    {
        return $this->scopeConfig->getValue(
            self::TIMEZONE_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Store's Country Code
     *
     * @return string
     */
    public function getStoreCountryCode(): string
    {
        return $this->scopeConfig->getValue(
            self::COUNTRY_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Store's Base Url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreBaseUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get the extension version
     *
     * @return string
     */
    public function getExtensionVersion(): string
    {
        return $this->systemConfig->getModuleVersion();
    }
}
