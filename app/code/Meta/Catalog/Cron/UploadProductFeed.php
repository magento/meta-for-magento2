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
use Meta\Catalog\Model\Product\Feed\Uploader;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;

class UploadProductFeed
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        SystemConfig $systemConfig,
        Uploader $uploader,
        FBEHelper $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Upload for product
     *
     * @param int $storeId
     * @return $this
     * @throws LocalizedException
     */
    private function uploadForStore($storeId)
    {
        if ($this->isFeedUploadEnabled($storeId)) {
            $this->uploader->uploadFullCatalog($storeId);
        }

        return $this;
    }

    /**
     * Return configuration state of feed upload
     *
     * @param int $storeId Store ID to check.
     * @return bool
     */
    private function isFeedUploadEnabled($storeId)
    {
        return $this->systemConfig->isCatalogSyncEnabled($storeId);
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getStoreManager()->getStores() as $store) {
            try {
                $this->uploadForStore($store->getId());
            } catch (Exception $e) {
                $context = [
                    'store_id' => $store->getId(),
                    'log_type' => 'persist_meta_log_immediately',
                    'event' => 'catalog_sync',
                    'event_type' => 'upload_product_feed_cron',
                ];
                $this->fbeHelper->logException($e, $context);
            }
        }
    }
}
