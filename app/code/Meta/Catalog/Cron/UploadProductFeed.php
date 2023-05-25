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

use Meta\Catalog\Model\Product\Feed\Uploader;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfig $systemConfig, Uploader $uploader, LoggerInterface $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
        $this->logger = $logger;
    }

    /**
     * Upload for product
     *
     * @param int $storeId
     * @return $this
     * @throws LocalizedException
     */
    protected function uploadForStore($storeId)
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
        if (!$this->systemConfig->isActiveExtension($storeId)) {
            return false;
        }
        return $this->systemConfig->isActiveDailyProductFeed($storeId);
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
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
