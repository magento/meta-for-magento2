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

namespace Meta\BusinessExtension\Model\Api;

use Exception;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Cron\UpdateMBESettings;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Api\SettingsWebhookListenerInterface;
use Meta\BusinessExtension\Api\SettingsWebhookRequestInterface;

class SettingsWebhookListenerImpl implements SettingsWebhookListenerInterface
{
    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var UpdateMBESettings
     */
    private UpdateMBESettings $updateMBESettings;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param UpdateMBESettings $updateMBESettings
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UpdateMBESettings $updateMBESettings,
        SystemConfig          $systemConfig,
        FBEHelper             $fbeHelper,
        CollectionFactory $collectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->systemConfig = $systemConfig;
        $this->updateMBESettings = $updateMBESettings;
        $this->fbeHelper = $fbeHelper;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Process webhook request
     *
     * @param SettingsWebhookRequestInterface[] $settingsWebhookRequest
     * @return void
     */
    public function processSettingsWebhookRequest(array $settingsWebhookRequest): void
    {
        foreach ($settingsWebhookRequest as $setting) {
            $this->updateSetting($setting);
        }
    }

    /**
     * Process webhook request
     *
     * @param SettingsWebhookRequestInterface $setting
     */
    private function updateSetting(SettingsWebhookRequestInterface $setting): void
    {
        // Step 1 - Get StoreId by business_extension_id
        $external_business_id = $setting->getExternalBusinessId();
        $installedConfigs = $this->getMBEInstalledConfigsByExternalBusinessId($external_business_id);

        if (empty($installedConfigs)) {
            $this->fbeHelper->log(
                'Skipping update MBESettings. No store id is found for external_business_id: {$external_business_id}'
            );
            return;
        }
        // StoreId and externalBusinessId is 1:1 mapping, hence get $storeIds[0] as $storeId in below.
        $storeId = $installedConfigs[0]->getScopeId();
        // Step 2
        // - Trigger Magento polling Graph API fbe_install,
        // - Store latest MBESettings in magento DB
        // (To see details of what config stored, find in SaveFBEInstallResponse->Save() function)
        $this->updateMBESettings->updateMBESettingsByStoreId((int)$storeId);

        // Step 3 TODO:
        // calling Catalog_Script(catalogId, pixel_id, storeId);
        $this->systemConfig->getCatalogId();
        $this->systemConfig->getPixelId();
    }

    /**
     * Get config values where MBE is installed for external_business_Id
     *
     * @param string $external_business_id
     * @return array
     */
    private function getMBEInstalledConfigsByExternalBusinessId(string $external_business_id): array
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection
                ->addFieldToFilter('scope', ['eq' => 'stores'])
                ->addFieldToFilter(
                    'path',
                    ['eq' => SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID]
                )
                ->addValueFilter($external_business_id)
                ->addFieldToSelect('scope_id');

            return $collection->getItems();
          
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
            return [];
        }
    }
}
