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

namespace Meta\Catalog\Model\Product\Feed;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Meta\Catalog\Model\Product\Feed\Method\BatchApi as MethodBatchApi;
use Meta\Catalog\Model\Product\Feed\Method\FeedApi as MethodFeedApi;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;

class Uploader
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var MethodBatchApi
     */
    private $methodBatchApi;

    /**
     * @var MethodFeedApi
     */
    private $methodFeedApi;

    /**
     * @param SystemConfig $systemConfig
     * @param MethodBatchApi $methodBatchApi
     * @param MethodFeedApi $methodFeedApi
     */
    public function __construct(
        SystemConfig $systemConfig,
        MethodBatchApi $methodBatchApi,
        MethodFeedApi $methodFeedApi
    ) {
        $this->systemConfig = $systemConfig;
        $this->methodBatchApi = $methodBatchApi;
        $this->methodFeedApi = $methodFeedApi;
    }

    /**
     * Upload Magento catalog to Facebook
     *
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return array
     * @throws \Throwable
     */
    public function uploadFullCatalog($storeId, string $flowName, string $traceId)
    {
        return $this->methodFeedApi->execute($storeId, $flowName, $traceId);
    }
}
