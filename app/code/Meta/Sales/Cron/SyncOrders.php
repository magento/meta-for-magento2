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

namespace Meta\Sales\Cron;

use Exception;
use Meta\Sales\Helper\CommerceHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\FBEHelper;

/**
 * Pulls pending orders from FB Commerce Account using FB Graph API
 */
class SyncOrders
{

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param CommerceHelper $commerceHelper
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        SystemConfig          $systemConfig,
        CommerceHelper        $commerceHelper,
        FBEHelper             $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Sync orders from facebook for a store
     *
     * @param int $storeId
     * @return void
     * @throws GuzzleException
     */
    private function pullOrdersForStore(int $storeId): void
    {
        // Only pull order if all the condition met:
        // 1. Order sync enabled
        // 2. Extension enabled(included in 1)
        // 3. There's any shop feature installed(fb_shop, page_shop, ig_shopping) and active
        // 4. The installed shop is eligible for onsite features
        if (!($this->systemConfig->isOrderSyncEnabled($storeId)
            && $this->systemConfig->isFBEShopInstalled($storeId)
            && $this->systemConfig->isInstalledShopOnsiteEligible($storeId))) {
            return;
        }

        $this->commerceHelper->pullPendingOrders($storeId);
    }

    /**
     * Sync orders from facebook for each magento store
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getAllOnsiteFBEInstalledStores() as $store) {
            try {
                $this->pullOrdersForStore((int)$store->getId());
            } catch (Exception $e) {
                $context = [
                    'store_id' => $store->getId(),
                    'event' => 'order_sync',
                    'event_type' => 'sync_orders_cron',
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
            }
        }
    }
}
