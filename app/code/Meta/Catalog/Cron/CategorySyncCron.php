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

namespace Meta\Catalog\Cron;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Feed\CategoryCollection;

class CategorySyncCron
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var CategoryCollection
     */
    protected $categoryCollection;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * CategorySyncCron constructor
     *
     * @param FBEHelper $fbeHelper
     * @param CategoryCollection $categoryCollection
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $fbeHelper,
        CategoryCollection $categoryCollection,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->categoryCollection = $categoryCollection;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Execute function for Category Sync
     *
     * @return bool
     */
    public function execute()
    {
        if ($this->systemConfig->isActiveCollectionsSync()) {
            $this->categoryCollection->pushAllCategoriesToFbCollections();
            return true;
        }
        return false;
    }
}
