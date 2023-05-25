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

namespace Meta\BusinessExtension\Model\System;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config as AppConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Config
{
    private const VERSION_CACHE_KEY = 'meta-business-extension-version';
    private const EXTENSION_PACKAGE_NAME = 'meta/meta-for-magento2';

    private const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE = 'facebook/business_extension/active';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED = 'facebook/business_extension/installed';

    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID =
        'facebook/business_extension/external_business_id';

    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID = 'facebook/business_extension/pixel_id';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS =
        'facebook/business_extension/pixel_aam_settings';

    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES = 'facebook/business_extension/profiles';

    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID = 'facebook/business_extension/page_id';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID = 'facebook/business_extension/catalog_id';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID =
        'facebook/business_extension/commerce_account_id';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID = 'facebook/business_extension/feed_id';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_OFFERS_FEED_ID = 'facebook/business_extension/offers_feed_id';
    private const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_STORE = 'facebook/business_extension/store';

    private const XML_PATH_FACEBOOK_DAILY_PRODUCT_FEED = 'facebook/catalog_management/daily_product_feed';
    private const XML_PATH_FACEBOOK_FEED_UPLOAD_METHOD = 'facebook/catalog_management/feed_upload_method';
    private const XML_PATH_FACEBOOK_PRODUCT_IDENTIFIER = 'facebook/catalog_management/product_identifier';
    private const XML_PATH_FACEBOOK_PRICE_INCL_TAX = 'facebook/catalog_management/price_incl_tax';
    private const XML_PATH_FACEBOOK_COLLECTIONS_SYNC_IS_ACTIVE = 'facebook/catalog_management/collections_sync';

    private const XML_PATH_FACEBOOK_SHIPPING_METHODS_STANDARD = 'facebook/shipping_methods/standard';
    private const XML_PATH_FACEBOOK_SHIPPING_METHODS_EXPEDITED = 'facebook/shipping_methods/expedited';
    private const XML_PATH_FACEBOOK_SHIPPING_METHODS_RUSH = 'facebook/shipping_methods/rush';

    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN = 'facebook/business_extension/access_token';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ACCESS_TOKEN =
        'facebook/business_extension/page_access_token';

    private const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INCREMENTAL_PRODUCT_UPDATES =
        'facebook/catalog_management/incremental_product_updates';

    private const XML_PATH_FACEBOOK_ENABLE_INVENTORY_UPLOAD = 'facebook/inventory_management/enable_inventory_upload';
    private const XML_PATH_FACEBOOK_USE_MULTI_SOURCE_INVENTORY =
        'facebook/inventory_management/use_multi_source_inventory';
    private const XML_PATH_FACEBOOK_INVENTORY_STOCK = 'facebook/inventory_management/inventory_stock';
    private const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_OUT_OF_STOCK_THRESHOLD =
        'facebook/inventory_management/out_of_stock_threshold';

    public const XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE = 'facebook/orders_sync/active';
    public const XML_PATH_FACEBOOK_ORDERS_SYNC_DEFAULT_ORDER_STATUS = 'facebook/orders_sync/default_order_status';
    public const XML_PATH_FACEBOOK_AUTO_SUBSCRIBE_TO_NEWSLETTER = 'facebook/orders_sync/auto_subscribe_to_newsletter';
    private const XML_PATH_FACEBOOK_ORDER_SHIP_EVENT = 'facebook/orders_sync/order_ship_event';

    private const XML_PATH_FACEBOOK_USE_DEFAULT_FULFILLMENT_LOCATION =
        'facebook/orders_sync/default_fulfillment_location';
    private const XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_STREET_LINE_1 = 'facebook/orders_sync/street_line1';
    private const XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_STREET_LINE_2 = 'facebook/orders_sync/street_line2';
    private const XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_COUNTRY_ID = 'facebook/orders_sync/country_id';
    private const XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_STATE = 'facebook/orders_sync/region_id';
    private const XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_CITY = 'facebook/orders_sync/city';
    private const XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_ZIP_CODE = 'facebook/orders_sync/postcode';

    private const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_DEBUG_MODE = 'facebook/debug/debug_mode';

    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION = 'facebook/api/version';
    public const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE = 'facebook/api/version_last_update';

    private const XML_PATH_FACEBOOK_CONVERSION_MANAGEMENT_ENABLE_SERVER_TEST =
        'facebook/conversion_management/enable_server_test';
    private const XML_PATH_FACEBOOK_CONVERSION_MANAGEMENT_SERVER_TEST_CODE =
        'facebook/conversion_management/server_test_code';

    private const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ENABLE_ONSITE_CHECKOUT_FLAG =
        'facebook/business_extension/onsite';

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private ResourceConfig $resourceConfig;

    /**
     * @var TypeListInterface
     */
    private TypeListInterface $cacheTypeList;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var ComposerInformation
     */
    private ComposerInformation $composerInformation;

    /**
     * Extension version
     *
     * @var string|null
     */
    private ?string $version = null;

    /**
     * @method __construct
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConfig $resourceConfig
     * @param TypeListInterface $cacheTypeList
     * @param CacheInterface $cache
     * @param ComposerInformation $composerInformation
     * @SuppressWarnings(PHPMD.ExcessivePublicCount)
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        TypeListInterface $cacheTypeList,
        CacheInterface $cache,
        ComposerInformation $composerInformation
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->cache = $cache;
        $this->composerInformation = $composerInformation;
    }

    /**
     * Get app id
     *
     * @return string
     */
    public function getAppId(): string
    {
        return '195311308289826';
    }

    /**
     * Get extension version
     *
     * @return string
     */
    public function getModuleVersion(): string
    {
        $this->version = $this->version ?: $this->cache->load(self::VERSION_CACHE_KEY);
        if (!$this->version) {
            $installedPackages = $this->composerInformation->getInstalledMagentoPackages();
            $extensionVersion = $installedPackages[self::EXTENSION_PACKAGE_NAME]['version'] ?? null;
            if (!empty($extensionVersion)) {
                $this->version = $extensionVersion;
            } else {
                $this->version = 'dev';
            }
            $this->cache->save($this->version, self::VERSION_CACHE_KEY, [AppConfig::CACHE_TAG]);
        }
        return $this->version;
    }

    /**
     * Get commerce manager url
     *
     * @param int $storeId
     * @return string
     */
    public function getCommerceManagerUrl($storeId = null): string
    {
        return sprintf('https://www.facebook.com/commerce/%s', $this->getCommerceAccountId($storeId));
    }

    /**
     * Get catalog manager url
     *
     * @param int $storeId
     * @return string
     */
    public function getCatalogManagerUrl($storeId = null): string
    {
        return sprintf('https://www.facebook.com/products/catalogs/%s/products', $this->getCatalogId($storeId));
    }

    /**
     * Get support url
     *
     * @param int $storeId
     * @return string
     */
    public function getSupportUrl($storeId = null): string
    {
        return sprintf('https://www.facebook.com/commerce/%s/support/', $this->getCommerceAccountId($storeId));
    }

    /**
     * Get promotions url
     *
     * @param int $storeId
     * @return string
     */
    public function getPromotionsUrl($storeId = null): string
    {
        return sprintf(
            'https://www.facebook.com/commerce/%s/promotions/discounts/',
            $this->getCommerceAccountId($storeId)
        );
    }

    /**
     * Is single store mode
     *
     * @method isSingleStoreMode
     * @return bool
     */
    public function isSingleStoreMode(): bool
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * Is active extension
     *
     * @param int $scopeId
     * @param string $scope
     * @return bool
     */
    public function isActiveExtension($scopeId = null, $scope = ScopeInterface::SCOPE_STORES): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE, $scopeId, $scope);
    }

    /**
     * Is fbe installed
     *
     * @param int $scopeId
     * @param string $scope
     * @return bool
     */
    public function isFBEInstalled($scopeId = null, $scope = ScopeInterface::SCOPE_STORES): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED,
            $scopeId,
            $scope
        );
    }

    /**
     * Is onsite checkout enabled
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return bool
     */
    public function isOnsiteCheckoutEnabled($scopeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ENABLE_ONSITE_CHECKOUT_FLAG,
            $scopeId,
            $scope
        );
    }

    /**
     * Get store manager
     *
     * @return StoreManagerInterface
     */
    public function getStoreManager(): StoreManagerInterface
    {
        return $this->storeManager;
    }

    /**
     * Get store id
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Is active incremental product updates
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isActiveIncrementalProductUpdates($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INCREMENTAL_PRODUCT_UPDATES,
            $scopeId,
            $scope
        );
    }

    /**
     * Is active inventory upload
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isActiveInventoryUpload($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_ENABLE_INVENTORY_UPLOAD, $scopeId, $scope);
    }

    /**
     * Use multi source inventory
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function useMultiSourceInventory($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_USE_MULTI_SOURCE_INVENTORY, $scopeId, $scope);
    }

    /**
     * Get inventory stock
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getInventoryStock($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_INVENTORY_STOCK, $scopeId, $scope);
    }

    /**
     * Get out of stock threshold
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getOutOfStockThreshold($scopeId = null, $scope = null)
    {
        return $this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_OUT_OF_STOCK_THRESHOLD,
            $scopeId,
            $scope
        );
    }

    /**
     * Is active order sync
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isActiveOrderSync($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE, $scopeId, $scope);
    }

    /**
     * Get default order status
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function getDefaultOrderStatus($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_ORDERS_SYNC_DEFAULT_ORDER_STATUS, $scopeId, $scope);
    }

    /**
     * Should use default fulfillment address
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function shouldUseDefaultFulfillmentAddress($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_USE_DEFAULT_FULFILLMENT_LOCATION, $scopeId, $scope);
    }

    /**
     * Get fulfillment address
     *
     * @param int $scopeId
     * @param int $scope
     * @return array
     */
    public function getFulfillmentAddress($scopeId = null, $scope = ScopeInterface::SCOPE_STORES): array
    {
        $address = [];

        $address['street_1'] = $this->getConfig(
            self::XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_STREET_LINE_1,
            $scopeId,
            $scope
        );
        $address['street_2'] = $this->getConfig(
            self::XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_STREET_LINE_2,
            $scopeId,
            $scope
        );
        $address['country'] = $this->getConfig(
            self::XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_COUNTRY_ID,
            $scopeId,
            $scope
        );
        $address['state'] = $this->getConfig(
            self::XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_STATE,
            $scopeId,
            $scope
        );
        $address['city'] = $this->getConfig(
            self::XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_CITY,
            $scopeId,
            $scope
        );
        $address['postal_code'] = $this->getConfig(
            self::XML_PATH_FACEBOOK_FULFILLMENT_LOCATION_ZIP_CODE,
            $scopeId,
            $scope
        );

        return $address;
    }

    /**
     * Is auto newsletter subscription on
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isAutoNewsletterSubscriptionOn($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_AUTO_SUBSCRIBE_TO_NEWSLETTER, $scopeId, $scope);
    }

    /**
     * Get order ship event
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @param int|null $scopeId
     * @param int $scope
     * @return string
     */
    public function getOrderShipEvent($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_ORDER_SHIP_EVENT, $scopeId, $scope);
    }

    /**
     * Get config
     *
     * @param string $configPath
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     * @todo implement method for getting boolean values
     */
    public function getConfig($configPath, $scopeId = null, $scope = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($configPath);
        }
        try {
            $value = $this->scopeConfig->getValue($configPath, $scope ?: ScopeInterface::SCOPE_STORE, $scopeId === null
                ? $this->storeManager->getStore()->getId() : $scopeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $value;
    }

    /**
     * Save config
     *
     * @param string $path
     * @param string|int $value
     * @param int $storeId
     * @return $this
     */
    public function saveConfig($path, $value, $storeId = null)
    {
        if ($storeId) {
            $this->resourceConfig->saveConfig($path, $value, ScopeInterface::SCOPE_STORES, $storeId);
        } else {
            $this->resourceConfig->saveConfig($path, $value);
        }
        return $this;
    }

    /**
     * Delete config
     *
     * @param string $path
     * @param int $storeId
     * @return $this
     */
    public function deleteConfig($path, $storeId = null)
    {
        if ($storeId) {
            $this->resourceConfig->deleteConfig($path, ScopeInterface::SCOPE_STORES, $storeId);
        } else {
            $this->resourceConfig->deleteConfig($path);
        }
        return $this;
    }

    /**
     * Clean cache
     *
     * @return $this
     */
    public function cleanCache()
    {
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        return $this;
    }

    /**
     * Disable extension for non default stores
     *
     * @return $this
     */
    public function disableExtensionForNonDefaultStores()
    {
        $storeManager = $this->getStoreManager();
        if (!$storeManager->isSingleStoreMode()) {
            $defaultStoreId = $storeManager->getDefaultStoreView()->getId();
            foreach ($storeManager->getStores() as $store) {
                if ($store->getId() !== $defaultStoreId) {
                    $this->saveConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE, 0, $store->getId());
                }
            }
        }
        return $this;
    }

    /**
     * Get access token
     *
     * @param int $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getAccessToken($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $scopeId, $scope);
    }

    /**
     * Get external business id
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getExternalBusinessId($scopeId = null, $scope = null)
    {
        return $this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * Get pixel id
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getPixelId($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID, $scopeId, $scope);
    }

    /**
     * Get pixel aam settings
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getPixelAamSettings($scopeId = null, $scope = null)
    {
        return $this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS,
            $scopeId,
            $scope
        );
    }

    /**
     * Get profiles
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getProfiles($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES, $scopeId, $scope);
    }

    /**
     * Get page id
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getPageId($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $scopeId, $scope);
    }

    /**
     * Get catalog id
     *
     * @param int $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getCatalogId($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $scopeId, $scope);
    }

    /**
     * Get commerce account id
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getCommerceAccountId($scopeId = null, $scope = null)
    {
        return $this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID,
            $scopeId,
            $scope
        );
    }

    /**
     * Is debug mode
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isDebugMode($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_DEBUG_MODE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get api version
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getApiVersion($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION, $scopeId, $scope);
    }

    /**
     * Get api version last update
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getApiVersionLastUpdate($scopeId = null, $scope = null)
    {
        return $this->getConfig(
            self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get shipping methods map
     *
     * @param int $storeId
     * @return array
     */
    public function getShippingMethodsMap($storeId = null): array
    {
        return [
            'standard' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_STANDARD, $storeId),
            'expedited' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_EXPEDITED, $storeId),
            'rush' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_RUSH, $storeId),
        ];
    }

    /**
     * Is active daily product feed
     *
     * @param int $scopeId
     * @param string $scope
     * @return bool
     */
    public function isActiveDailyProductFeed($scopeId = null, $scope = ScopeInterface::SCOPE_STORES): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_DAILY_PRODUCT_FEED, $scopeId, $scope);
    }

    /**
     * Get feed upload method
     *
     * @param int $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getFeedUploadMethod($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_FEED_UPLOAD_METHOD, $scopeId, $scope);
    }

    /**
     * Get feed id
     *
     * @param int $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getFeedId($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $scopeId, $scope);
    }

    /**
     * Get offers feed id
     *
     * @param int $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getOffersFeedId($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_OFFERS_FEED_ID, $scopeId, $scope);
    }

    /**
     * Get product identifier attr
     *
     * @param int $scopeId
     * @param int $scope
     * @return mixed
     */
    public function getProductIdentifierAttr($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_PRODUCT_IDENTIFIER, $scopeId, $scope);
    }

    /**
     * Is price incl tax
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isPriceInclTax($scopeId = null, $scope = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_PRICE_INCL_TAX, $scopeId, $scope);
    }

    /**
     * Is active collections sync
     *
     * @param int $scopeId
     * @param int $scope
     * @return bool
     */
    public function isActiveCollectionsSync($scopeId = null, $scope = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FACEBOOK_COLLECTIONS_SYNC_IS_ACTIVE,
            $scopeId = null,
            $scope = null
        );
    }

    /**
     * Check if test mode enabled for the server events
     *
     * @param int|null $scopeId
     * @param string|null $scope
     * @return bool
     */
    public function isServerTestModeEnabled(int $scopeId = null, string $scope = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_FACEBOOK_CONVERSION_MANAGEMENT_ENABLE_SERVER_TEST,
            $scopeId,
            $scope
        );
    }

    /**
     * Get server event test code
     *
     * @param int|null $scopeId
     * @param string|null $scope
     * @return string
     */
    public function getServerTestCode(int $scopeId = null, string $scope = null): string
    {
        return $this->getConfig(
            self::XML_PATH_FACEBOOK_CONVERSION_MANAGEMENT_SERVER_TEST_CODE,
            $scopeId,
            $scope
        );
    }

    /**
     * Get store weight unit
     *
     * @param int|null $scopeId
     * @param string|null $scope
     * @return mixed
     */
    public function getWeightUnit(int $scopeId = null, string $scope = null)
    {
        return $this->getConfig('general/locale/weight_unit', $scopeId, $scope);
    }
}
