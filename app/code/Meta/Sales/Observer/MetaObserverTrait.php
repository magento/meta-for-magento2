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

namespace Meta\Sales\Observer;

use Magento\Framework\Event\Observer;
use Meta\BusinessExtension\Helper\FBEHelper;
use Throwable;

trait MetaObserverTrait
{
    /**
     * Execute function
     *
     * @param Observer $observer
     * @return void
     * @throws Throwable
     */
    public function execute(Observer $observer)
    {
        try {
            $this->executeImpl($observer);
        } catch (Throwable $e) {
            $storeId = $this->getStoreId($observer);
            $this->getFBEHelper()->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => $this->getExceptionEvent() ?? "order_observer_exception",
                    'event_type' => strtolower(get_class($e))
                ]
            );
            throw $e;
        }
    }

    /**
     * Implementation of the execute function
     *
     * @param Observer $observer
     * @return void
     */
    abstract protected function executeImpl(Observer $observer);

    /**
     * Get Facebook Event Helper
     *
     * @return FBEHelper
     */
    abstract protected function getFBEHelper();

    /**
     * Get Store ID
     *
     * @param Observer $observer
     * @return string
     */
    abstract protected function getStoreId(Observer $observer);

    /**
     * Get Exception Event
     *
     * @return string
     */
    abstract protected function getExceptionEvent();
}
