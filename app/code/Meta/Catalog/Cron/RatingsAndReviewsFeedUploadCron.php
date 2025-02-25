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
use Meta\Catalog\Model\Product\Feed\Method\RatingsAndReviewsFeedApi;

class RatingsAndReviewsFeedUploadCron
{
    /**
     * @var RatingsAndReviewsFeedApi
     */
    private RatingsAndReviewsFeedApi $ratingsAndReviewsFeedApi;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * RatingsAndReviewsFeedUploadCron constructor
     *
     * @param RatingsAndReviewsFeedApi $ratingsAndReviewsFeedApi
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        RatingsAndReviewsFeedApi  $ratingsAndReviewsFeedApi,
        SystemConfig              $systemConfig,
        FBEHelper                 $fbeHelper
    ) {
        $this->ratingsAndReviewsFeedApi = $ratingsAndReviewsFeedApi;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Sync ratings and reviews from Magento platform to Meta through feed upload API
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getAllFBEInstalledStores() as $store) {
            $storeId = (int)$store->getId();
            try {
                if ($this->systemConfig->isCatalogSyncEnabled($storeId)) {
                    $this->ratingsAndReviewsFeedApi->execute($storeId);
                }
            } catch (\Throwable $e) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                      'store_id' => $storeId,
                      'event' => 'ratings_and_reviews_sync',
                      'event_type' => 'cron_job',
                      'catalog_id' => $this->systemConfig->getCatalogId($storeId),
                    ]
                );
            }
        }
    }
}
