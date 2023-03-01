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

namespace Meta\Catalog\Model\Product\Feed;

use Exception;
use Meta\Catalog\Model\Config\Source\FeedUploadMethod;
use Meta\Catalog\Model\Product\Feed\Method\BatchApi as MethodBatchApi;
use Meta\Catalog\Model\Product\Feed\Method\FeedApi as MethodFeedApi;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;

class Uploader
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var MethodBatchApi
     */
    protected $methodBatchApi;

    /**
     * @var MethodFeedApi
     */
    protected $methodFeedApi;

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
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function uploadFullCatalog($storeId = null)
    {
        $uploadMethod = $this->systemConfig->getFeedUploadMethod($storeId);

        if ($uploadMethod === FeedUploadMethod::UPLOAD_METHOD_CATALOG_BATCH_API) {
            try {
                $response = $this->methodBatchApi->generateProductRequestData($storeId);
            } catch (Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        } elseif ($uploadMethod === FeedUploadMethod::UPLOAD_METHOD_FEED_API) {
            $response = $this->methodFeedApi->execute($storeId);
        } else {
            throw new LocalizedException(__('Unknown feed upload method'));
        }
        return $response;
    }

    /**
     * Upload product inventory to Facebook
     *
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    public function uploadInventory($storeId = null)
    {
        try {
            $response = $this->methodBatchApi->generateProductRequestData($storeId, null, true);
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        return $response;
    }
}
