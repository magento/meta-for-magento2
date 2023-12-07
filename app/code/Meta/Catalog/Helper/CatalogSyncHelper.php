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

namespace Meta\Catalog\Helper;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Category\CategoryCollection;
use Meta\Catalog\Model\Product\Feed\Uploader;

class CatalogSyncHelper
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
     * @var Uploader
     */
    private $uploader;

    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * Helper class for syncing Catalog
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     * @param CategoryCollection $categoryCollection
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        Uploader $uploader,
        CategoryCollection $categoryCollection
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * Syncs all products and categories to Meta Catalog
     *
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return void
     */
    public function syncFullCatalog(int $storeId, string $flowName, string $traceId)
    {
        try {
            if ($this->systemConfig->isCatalogSyncEnabled($storeId)) {
                $this->uploader->uploadFullCatalog($storeId, $flowName, $traceId);
                $this->categoryCollection->pushAllCategoriesToFbCollections($storeId);
            }
        } catch (\Throwable $e) {
            $context = [
                'store_id' => $storeId,
                'event' => 'full_catalog_sync',
                'event_type' => 'all_products_and_categories_sync',
                'catalog_id' => $this->systemConfig->getCatalogId($storeId),
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
        }
    }
}
