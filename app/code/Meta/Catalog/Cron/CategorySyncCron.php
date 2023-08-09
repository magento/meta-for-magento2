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

use Exception;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Feed\CategoryCollection;
use Meta\BusinessExtension\Helper\FBEHelper;

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
        SystemConfig $systemConfig,
        FBEHelper $fbeHelper
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Execute function for Category Sync
     *
     * @return bool
     */
    public function execute()
    {
        if (!$this->systemConfig->isActiveCollectionsSync()) {
            return false;
        }
        try {
            $this->categoryCollection->pushAllCategoriesToFbCollections();
            return true;
        } catch (Exception $e) {
            $context = [
                'store_id' => $this->fbeHelper->getStore()->getId(),
                'log_type' => 'persist_meta_log_immediately',
                'event' => 'category_sync',
                'event_type' => 'category_sync_cron',
            ];
            $this->fbeHelper->logException($e, $context);
            return false;
        }
    }
}
