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

use Exception;
use Meta\Catalog\Model\Product\Feed\Uploader;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class UploadInventory
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
     * Upload inventory for store
     *
     * @param int $storeId
     * @return $this
     * @throws LocalizedException
     */
    protected function uploadForStore($storeId)
    {
        if ($this->isUploadEnabled($storeId)) {
            $this->uploader->uploadInventory($storeId);
        }

        return $this;
    }

    /**
     * Check configuration state of upload
     *
     * @param int $storeId Store ID to check.
     * @return bool
     */
    private function isUploadEnabled($storeId)
    {
        if (!$this->systemConfig->isActiveExtension($storeId)) {
            return false;
        }
        return $this->systemConfig->isActiveInventoryUpload($storeId);
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
                $this->logger->critical($e);
            }
        }
    }
}
