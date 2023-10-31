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

namespace Meta\Catalog\Cron;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Category\CategoryCollection;

class CategorySyncCron
{
    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * CategorySyncCron constructor
     *
     * @param CategoryCollection $categoryCollection
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        CategoryCollection $categoryCollection,
        SystemConfig       $systemConfig,
        FBEHelper          $fbeHelper
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Execute function for Category Sync
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getAllFBEInstalledStores() as $store) {
            $storeId = $store->getId();
            try {
                if ($this->systemConfig->isCatalogSyncEnabled($storeId)) {
                    $this->categoryCollection->pushAllCategoriesToFbCollections($storeId);
                }
            } catch (\Throwable $e) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'category_sync',
                        'event_type' => 'category_sync_cron',
                        'catalog_id' => $this->systemConfig->getCatalogId($storeId)
                    ]
                );
            }
        }
    }
}
