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

namespace Meta\Promotions\Model\Promotion\Feed;

use Exception;
use Meta\Promotions\Model\Promotion\Feed\Method\FeedApi as MethodFeedApi;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class Uploader
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var MethodFeedApi
     */
    protected $methodFeedApi;

    /**
     * @param SystemConfig $systemConfig
     * @param MethodFeedApi $methodFeedApi
     */
    public function __construct(
        SystemConfig  $systemConfig,
        MethodFeedApi $methodFeedApi
    ) {
        $this->systemConfig = $systemConfig;
        $this->methodFeedApi = $methodFeedApi;
    }

    /**
     * Upload Magento promotions to Facebook
     *
     * @param int $storeId
     * @return array
     * @throws Exception
     */
    public function uploadPromotions($storeId = null)
    {
        return $this->methodFeedApi->execute($storeId);
    }
}
