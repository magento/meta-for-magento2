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

namespace Meta\Catalog\Observer\Facebook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Helper\CatalogSyncHelper;
use Throwable;

class SyncFullCatalog implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var CatalogSyncHelper
     */
    private CatalogSyncHelper $catalogSyncHelper;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param CatalogSyncHelper $catalogSyncHelper
     */
    public function __construct(
        FBEHelper         $fbeHelper,
        CatalogSyncHelper $catalogSyncHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->catalogSyncHelper = $catalogSyncHelper;
    }

    /**
     * Execute observer on FBE onboarding completion
     *
     * Immediately after onboarding we initiate full catalog sync.
     * It syncs all products and all categories to Meta Catalog
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $storeId = $observer->getEvent()->getStoreId();

        try {
            $this->catalogSyncHelper->syncFullCatalog($storeId);
        } catch (Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'full_catalog_sync',
                    'event_type' => 'meta_observer'
                ]
            );
        }
    }
}
