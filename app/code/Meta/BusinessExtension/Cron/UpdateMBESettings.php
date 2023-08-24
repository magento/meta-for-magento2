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

namespace Meta\BusinessExtension\Cron;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\SaveFBEInstallsResponse;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;

class UpdateMBESettings
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
    private $graphAPIAdapter;

    /**
     * @var SaveFBEInstallsResponse
     */
    private $saveFBEInstallsResponse;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Construct
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param SaveFBEInstallsResponse $saveFBEInstallsResponse
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphAPIAdapter,
        SaveFBEInstallsResponse $saveFBEInstallsResponse,
        CollectionFactory $collectionFactory
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->saveFBEInstallsResponse = $saveFBEInstallsResponse;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute cron
     */
    public function execute()
    {
        $installedConfigs = $this->getMBEInstalledConfigs();
        foreach ($installedConfigs as $config) {
            $this->updateMBESettings($config->getScopeId());
        }
    }

    /**
     * Update MBE settings through the 'fbe_installs' API
     *
     * @param int $storeId
     * @return void
     * @throws GuzzleException
     */
    private function updateMBESettings($storeId)
    {
        try {
            $accessToken = $this->systemConfig->getAccessToken($storeId);
            $businessId = $this->systemConfig->getExternalBusinessId($storeId);
            if (!$accessToken || !$businessId) {
                $this->fbeHelper->log("AccessToken or BusinessID not found for storeID: {$storeId}");
                return;
            }
            $response = $this->graphAPIAdapter->getFBEInstalls($accessToken, $businessId);
            $this->saveFBEInstallsResponse->save($response['data'], $storeId);
            $this->fbeHelper->log("Updated MBE Settings for storeId: {$storeId}");
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'update_mbe_settings_cron',
                    'event_type' => 'update_mbe_settings'
                ]
            );
        }
    }

    /**
     * Get config values where MBE is installed for store_id
     *
     * @return array
     */
    private function getMBEInstalledConfigs()
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection
                ->addFieldToFilter('scope', ['eq' => 'stores'])
                ->addFieldToFilter('path', ['eq' => SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED])
                ->addValueFilter(1)
                ->addFieldToSelect('scope_id');

            return $collection->getItems();
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $this->fbeHelper->getStore()->getId(),
                    'event' => 'update_mbe_settings_cron',
                    'event_type' => 'get_mbe_installed_configs'
                ]
            );
            return [];
        }
    }
}
