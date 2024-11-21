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

use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\ProductMetadataInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class EnabledModuleLogging
{
    /**
     * @var GraphAPIAdapter
     */
    private $graphAPIAdapter;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Construct
     *
     * @param GraphAPIAdapter           $graphAPIAdapter
     * @param ModuleList                $moduleList
     * @param SystemConfig              $systemConfig
     * @param ProductMetadataInterface  $productMetadata
     */
    public function __construct(
        GraphAPIAdapter             $graphAPIAdapter,
        ModuleList                  $moduleList,
        SystemConfig                $systemConfig,
        ProductMetadataInterface    $productMetadata
    ) {
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->moduleList = $moduleList;
        $this->systemConfig = $systemConfig;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Execute cron
     */
    public function execute()
    {
        $cmsIds = [];
        $accessToken = null;
        foreach ($this->systemConfig->getAllFBEInstalledStores() as $store) {
            $storeId = $store->getId();
            $cmsId = $this->systemConfig->getCommerceAccountId($storeId);
            $cmsIds[] = $cmsId;
            $storeAccessToken = $this->systemConfig->getAccessToken($storeId);
            if ($storeAccessToken) {
                $accessToken = $storeAccessToken;
            }
        }
        $this->graphAPIAdapter->persistLogToMeta(
            [
                'event' => 'commerce_plugin_and_extension_logging',
                'event_type' => 'enabled_modules',
                'seller_platform_app_version' => $this->productMetadata->getVersion(),
                'extra_data' => [
                    'enabled_modules' => json_encode($this->moduleList->getNames()),
                    'extension_version' => $this->systemConfig->getModuleVersion(),
                    'cms_ids' => json_encode($cmsIds)
                ]
            ],
            $accessToken
        );
    }
}
