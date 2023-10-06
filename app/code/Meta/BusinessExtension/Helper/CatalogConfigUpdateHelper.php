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

namespace Meta\BusinessExtension\Helper;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class CatalogConfigUpdateHelper
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param EventManager $eventManager
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        EventManager $eventManager
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->eventManager = $eventManager;
    }

    /**
     * Function updates catalog config if updated on Meta side after onboarding flow completion on Meta side.
     * Function enables Meta Commerce Sync and do full catalog sync.
     * Do not call it apart from catalog update webhook event, it may cause stale state for catalog integration.
     *
     * @param int $storeId
     * @param string $catalogId
     * @param string $commercePartnerIntegrationId
     * @param string $pixelId
     * @param bool $triggerFullSync
     * @return void
     */
    public function updateCatalogConfiguration(
        int $storeId,
        string $catalogId,
        string $commercePartnerIntegrationId,
        string $pixelId,
        bool $triggerFullSync = true,
    ): void {
        $oldCatalogId = $this->systemConfig->getCatalogId($storeId);
        try {
            $isCatalogUpdated = $oldCatalogId != $catalogId;

            // if Catalog id is updated, only then we update the config
            if ($isCatalogUpdated) {
                // updates catalog id
                $this->systemConfig->saveConfig(
                    SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID,
                    $catalogId,
                    $storeId
                );
                // updates commerce partner integration id
                $this->systemConfig->saveConfig(
                    SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_PARTNER_INTEGRATION_ID,
                    $commercePartnerIntegrationId,
                    $storeId
                );
                // updates pixel id
                $this->systemConfig->saveConfig(
                    SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID,
                    $pixelId,
                    $storeId
                );
                // delete feed id as new catalog would be having new feed id.
                $this->systemConfig->deleteConfig(
                    SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID,
                    $storeId
                );
                // Dispatch the facebook_update_catalog_configuration event
                // so observer in other Meta modules can subscribe and update required configurations
                // along with configuration updated in this file.
                // for e.g. clears all the product set ids for categories for old catalog
                $this->eventManager->dispatch('facebook_update_catalog_configuration', ['store_id' => $storeId]);

                $this->fbeHelper->log('Catalog configuration updated and stale product set ids cleared --- '
                    . $commercePartnerIntegrationId);

            }

            $this->systemConfig->cleanCache();

            // Dispatch the facebook_update_catalog_configuration_after event,
            // so observers in other Meta modules can subscribe and trigger their syncs,
            // such as full catalog sync, and shipping profiles sync
            if ($triggerFullSync || $isCatalogUpdated) {
                $this->eventManager->dispatch('facebook_update_catalog_configuration_after', ['store_id' => $storeId]);
            }
        } catch (\Throwable $e) {
            $context = [
                'store_id' => $storeId,
                'event' => 'update_catalog_config',
                'event_type' => 'update_catalog_config',
                'catalog_id' => $catalogId,
                'old_catalog_id' => $oldCatalogId,
                'commerce_partner_integration_id' => $commercePartnerIntegrationId,
                'pixel_id' => $pixelId
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
        }
    }
}
