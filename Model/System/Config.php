<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\System;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    const XML_PATH_FACEBOOK_COLLECTIONS_SYNC_IS_ACTIVE = 'facebook/catalog_management/collections_sync';

    const XML_PATH_FACEBOOK_INVENTORY_SOURCE = 'facebook/inventory_management/inventory_source';

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
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @method __construct
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConfig $resourceConfig
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        TypeListInterface $cacheTypeList
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
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
     * @param $path
     * @param $value
     * @return $this
     */
    public function saveConfig($path, $value)
    {
        $this->resourceConfig->saveConfig($path, $value);
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
     * @return bool
     */
    public function isActiveCollectionsSync()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_FACEBOOK_COLLECTIONS_SYNC_IS_ACTIVE);
    }

    /**
     * @return mixed
     */
    public function getInventorySource()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FACEBOOK_INVENTORY_SOURCE);
    }
}
