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

namespace Meta\Sales\Plugin;

use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\BusinessExtension\Logger\Logger;

class TransactionUpdatePlugin
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private FacebookOrderInterfaceFactory $facebookOrderFactory;

    /**
     * @param Logger $logger
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     */
    public function __construct(
        Logger $logger,
        FacebookOrderInterfaceFactory $facebookOrderFactory
    ) {
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->logger = $logger;
    }

    /**
     * Overriding the GetProvider method of Taxjar to return Facebook to exempt the order in
     *
     * Taxjar plugin with marketplace exemption
     *
     * @param mixed $subject
     * @param mixed $result
     * @param mixed $order
     * @return string
     */
    public function afterGetProvider(mixed $subject, $result, $order): string
    {
        // Logging $subject to prevent Magento's internal tests from failing due to unused var. $_ doesn't work.
        $this->logger->debug($subject);
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($order->getId(), 'magento_order_id');
        if ($facebookOrder->getFacebookOrderId()) {
            return 'facebook';
        }
        return $result;
    }
}
