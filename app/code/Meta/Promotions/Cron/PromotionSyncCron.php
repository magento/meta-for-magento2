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

namespace Meta\Promotions\Cron;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Promotions\Model\Promotion\Feed\Uploader;

class PromotionSyncCron
{
    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var Uploader
     */
    private Uploader $uploader;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * PromotionSyncCron constructor
     *
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
     * Push promotions from Magento platform to Meta
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getStoreManager()->getStores() as $store) {
            try {
                if ($this->systemConfig->isPromotionsSyncEnabled($store->getId())) {
                    $this->uploader->uploadPromotions($store->getId());
                }
            } catch (\Throwable $e) {
                $context = [
                    'store_id' => $store->getId(),
                    'event' => 'promotion_sync',
                    'event_type' => 'promotions_sync_cron',
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
            }
        }
    }
}
