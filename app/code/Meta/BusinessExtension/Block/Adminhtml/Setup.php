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
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\CommerceExtensionHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

/**
 * @api
 */
class Setup extends Template
{
    /**
     * @var ApiKeyService
     */
    private $apiKeyService;
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var StoreRepositoryInterface
     */
    public $storeRepo;

    /**
     * @var WebsiteCollectionFactory
     */
    private $websiteCollectionFactory;

    /**
     * @var CommerceExtensionHelper
     */
    private $commerceExtensionHelper;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param StoreRepositoryInterface $storeRepo
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param CommerceExtensionHelper $commerceExtensionHelper
     * @param ApiKeyService $apiKeyService
     * @param array $data
     */
    public function __construct(
        Context                  $context,
        RequestInterface         $request,
        FBEHelper                $fbeHelper,
        SystemConfig             $systemConfig,
        StoreRepositoryInterface $storeRepo,
        WebsiteCollectionFactory $websiteCollectionFactory,
        CommerceExtensionHelper $commerceExtensionHelper,
        ApiKeyService            $apiKeyService,
        array                    $data = []
    ) {
        $this->fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
        $this->request = $request;
        $this->systemConfig = $systemConfig;
        $this->storeRepo = $storeRepo;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->commerceExtensionHelper = $commerceExtensionHelper;
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * ID of the selected Store.
     *
     * @return string|null
     */
    public function getSelectedStoreId()
    {
        $stores = $this->getSelectableStores();
        if (empty($stores)) {
            return null;
        }

        // If there is a store matching query param, return it.
        $requestStoreId = $this->request->getParam('store_id');
        try {
            $this->storeRepo->getById($requestStoreId);
            return $requestStoreId;
        } catch (NoSuchEntityException $e) {
            // Store not found, fallback to default store selection logic.
            $requestStoreId = null;
        }

        // Missing or invalid query param, look for a default.
        foreach ($stores as $store) {
            if ($store->isDefault() && $store->getWebsiteId() === $this->getFirstWebsiteId()) {
                return $store['store_id'];
            }
        }


        // No default found, return the first store.
        $firstStore = array_shift($stores);
        return $firstStore['store_id'];
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
        $storeId = $this->fbeHelper->getStore()->getId();
        $this->fbeHelper->log("Store id---" . $storeId);
        return uniqid('fbe_magento_' . $storeId . '_');
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
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
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
     * @return string
     */
    public function hasCommerceExtensionIFramePermissionError($storeId)
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
     * Get stores that are selectable (not Admin).
     *
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
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
     * Get first website id
     *
     * @return int|null
     */
    public function getFirstWebsiteId()
    {
        $collection = $this->websiteCollectionFactory->create();
        $collection->addFieldToSelect('website_id')
            ->addFieldToFilter('code', ['neq' => 'admin']);
        $collection->getSelect()->order('website_id ASC')->limit(1);

        return $collection->getFirstItem()->getWebsiteId();
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
     * @return string
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
     * Call this method to Get the existing Api key or generate and return it.
     *
     * @return string
     */
    public function getCustomApiKey(): string
    {
        return $this->apiKeyService->getCustomApiKey();
    }
}
