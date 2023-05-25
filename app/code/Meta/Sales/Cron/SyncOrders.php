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

namespace Meta\Sales\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Meta\Sales\Helper\CommerceHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Pulls pending orders from FB Commerce Account using FB Graph API
 */
class SyncOrders
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SystemConfig $systemConfig
     * @param CommerceHelper $commerceHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SystemConfig $systemConfig,
        CommerceHelper $commerceHelper,
        LoggerInterface $logger
    ) {
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * Sync orders from facebook for a store
     *
     * @param int $storeId
     * @return void
     * @throws GuzzleException
     */
    private function pullOrdersForStore(int $storeId)
    {
        if (!($this->systemConfig->isActiveExtension($storeId)
            && $this->systemConfig->isActiveOrderSync($storeId)
            && $this->systemConfig->isOnsiteCheckoutEnabled($storeId))) {
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
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $this->pullOrdersForStore((int)$store->getId());
            } catch (GuzzleException $e) {
                $this->logger->critical($e);
            }
        }
    }
}
