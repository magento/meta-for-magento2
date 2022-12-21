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

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Pulls pending orders from FB Commerce Account using FB Graph API
 */
class SyncOrders
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var CommerceHelper
     */
    private $commerceHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SystemConfig $systemConfig
     * @param CommerceHelper $commerceHelper
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfig $systemConfig, CommerceHelper $commerceHelper, LoggerInterface $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
        $this->logger = $logger;
    }

    /**
     * @param $storeId
     * @throws GuzzleException
     */
    protected function pullOrdersForStore($storeId)
    {
        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }
        $this->commerceHelper->setStoreId($storeId)
            ->pullPendingOrders();
    }

    public function execute()
    {
        foreach ($this->systemConfig->getStoreManager()->getStores() as $store) {
            try {
                $this->pullOrdersForStore($store->getId());
            } catch (GuzzleException $e) {
                $this->logger->critical($e);
            }
        }
    }
}
