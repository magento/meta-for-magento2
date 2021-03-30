<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\System;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    const MODULE_NAME = 'Facebook_BusinessExtension';

    const ONBOARDING_STATE_PENDING = 0;
    const ONBOARDING_STATE_IN_PROGRESS_NEW_SHOP = 1;
    const ONBOARDING_STATE_IN_PROGRESS_EXISTING_SHOP = 2;
    const ONBOARDING_STATE_COMPLETED = 4;

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE = 'facebook/business_extension/onboarding_state';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE = 'facebook/business_extension/active';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID = 'facebook/business_extension/page_id';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID = 'facebook/business_extension/catalog_id';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID = 'facebook/business_extension/commerce_account_id';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID = 'facebook/business_extension/feed_id';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_STORE = 'facebook/business_extension/store';

    const XML_PATH_FACEBOOK_DAILY_PRODUCT_FEED = 'facebook/catalog_management/daily_product_feed';
    const XML_PATH_FACEBOOK_FEED_UPLOAD_METHOD = 'facebook/catalog_management/feed_upload_method';
    const XML_PATH_FACEBOOK_PRODUCT_IDENTIFIER = 'facebook/catalog_management/product_identifier';
    const XML_PATH_FACEBOOK_COLLECTIONS_SYNC_IS_ACTIVE = 'facebook/catalog_management/collections_sync';

    const XML_PATH_FACEBOOK_SHIPPING_METHODS_STANDARD = 'facebook/shipping_methods/standard';
    const XML_PATH_FACEBOOK_SHIPPING_METHODS_EXPEDITED = 'facebook/shipping_methods/expedited';
    const XML_PATH_FACEBOOK_SHIPPING_METHODS_RUSH = 'facebook/shipping_methods/rush';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN = 'facebook/business_extension/access_token';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INCREMENTAL_PRODUCT_UPDATES = 'facebook/catalog_management/incremental_product_updates';

    const XML_PATH_FACEBOOK_INVENTORY_SOURCE = 'facebook/inventory_management/inventory_source';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_OUT_OF_STOCK_THRESHOLD = 'facebook/inventory_management/out_of_stock_threshold';

    const XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE = 'facebook/orders_sync/active';
    const XML_PATH_FACEBOOK_ORDERS_SYNC_DEFAULT_ORDER_STATUS = 'facebook/orders_sync/default_order_status';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_DEBUG_MODE = 'facebook/debug/debug_mode';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @method __construct
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConfig $resourceConfig
     * @param ModuleListInterface $moduleList
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        ModuleListInterface $moduleList,
        TypeListInterface $cacheTypeList
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->moduleList = $moduleList;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return '1718676831733203';
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getCommerceManagerUrl()
    {
        return sprintf('https://www.facebook.com/commerce_manager/%s', $this->getCommerceAccountId());
    }

    public function getCatalogManagerUrl()
    {
        return sprintf('https://www.facebook.com/products/catalogs/%s/products', $this->getCatalogId());
    }

    public function getSupportUrl()
    {
        return sprintf('https://www.facebook.com/commerce_manager/%s/support/', $this->getCommerceAccountId());
    }

    /**
     * @method isSingleStoreMode
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getOnboardingState($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ONBOARDING_STATE, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveExtension($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE, $scopeId, $scope);
    }

    /**
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        $storeId = $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_STORE);
        return $storeId >= 0 ? $storeId : $this->storeManager->getStore()->getId();
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveIncrementalProductUpdates($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INCREMENTAL_PRODUCT_UPDATES, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getInventorySource($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_INVENTORY_SOURCE, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getOutOfStockThreshold($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_OUT_OF_STOCK_THRESHOLD, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveOrderSync($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function getDefaultOrderStatus($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_ORDERS_SYNC_DEFAULT_ORDER_STATUS, $scopeId, $scope);
    }

    /**
     * @param $configPath
     * @param null $scopeId
     * @param null $scope
     * @todo implement method for getting boolean values
     * @return mixed
     */
    public function getConfig($configPath, $scopeId = null, $scope = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($configPath);
        }
        try {
            $value = $this->scopeConfig->getValue($configPath, $scope ?: ScopeInterface::SCOPE_STORE, is_null($scopeId)
                ? $this->storeManager->getStore()->getId() : $scopeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $value;
    }

    /**
     * @param $path
     * @param $value
     * @param null $storeId
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
     * @param $path
     * @return $this
     */
    public function deleteConfig($path)
    {
        $this->resourceConfig->deleteConfig($path);
        return $this;
    }

    /**
     * @return $this
     */
    public function cleanCache()
    {
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        return $this;
    }

    /**
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
     * @param null $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getAccessToken($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getPageId($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param string $scope
     * @return mixed
     */
    public function getCatalogId($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getCommerceAccountId($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isDebugMode($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_DEBUG_MODE, $scopeId, $scope);
    }

    /**
     * @return array
     */
    public function getShippingMethodsMap()
    {
        return [
            'standard' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_STANDARD),
            'expedited' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_EXPEDITED),
            'rush' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_RUSH),
        ];
    }

    public function isActiveDailyProductFeed($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_DAILY_PRODUCT_FEED, $scopeId, $scope);
    }

    public function getFeedUploadMethod($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_FEED_UPLOAD_METHOD, $scopeId, $scope);
    }

    public function getFeedId($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $scopeId, $scope);
    }

    public function getProductIdentifierAttr($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_PRODUCT_IDENTIFIER, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveCollectionsSync($scopeId = null, $scope = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_FACEBOOK_COLLECTIONS_SYNC_IS_ACTIVE, $scopeId = null, $scope = null);
    }
}
