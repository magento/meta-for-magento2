<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Store implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function getStores()
    {
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->isActive()) {
                continue;
            }

            try {
                $website = $this->storeManager->getWebsite($store->getWebsiteId());
            } catch (LocalizedException $e) {
                continue;
            }

            $stores[$store->getId()] = $website->getName() . ' -> ' . $store->getFrontendName();
        }

        return $stores;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $options = [['value' => -1, 'label' => __('Choose a store')]];
        foreach ($this->getStores() as $storeId => $storeName) {
            $options[] = ['value' => $storeId, 'label' => $storeName];
        }
        return $options;
    }
}
