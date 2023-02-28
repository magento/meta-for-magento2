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

namespace Meta\BusinessExtension\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Store implements OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Get map of store ids to names
     *
     * Retrieves an array of stores indexed by store ID, with a readable string value.
     *
     * @return array
     * @throws NoSuchEntityException
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
