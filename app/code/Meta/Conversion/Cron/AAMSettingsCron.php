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

namespace Meta\Conversion\Cron;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Store\Model\StoreManager;

class AAMSettingsCron
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
     * @var StoreManager
     */
    private $storeManager;

    /**
     * AAMSettingsCron constructor
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param StoreManager $storeManager
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        StoreManager $storeManager
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute
     *
     * @return bool
     */
    public function execute(): bool
    {
        $storeIds = array_keys($this->storeManager->getStores());
        $processed = false;
        foreach ($storeIds as $storeId) {
            $pixelId = $this->systemConfig->getPixelId($storeId);
            $settingsAsString = null;
            if ($pixelId) {
                $settingsAsString = $this->fbeHelper->fetchAndSaveAAMSettings($pixelId, $storeId);
                if (!$settingsAsString) {
                    $this->fbeHelper->log('Error saving settings. Currently:', $settingsAsString);
                } else {
                    $processed = true;
                }
            }
        }

        return $processed;
    }
}
