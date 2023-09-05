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

namespace Meta\Sales\Observer\Facebook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Plugin\ShippingSyncer;
use Throwable;

class SyncShippingProfiles implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var ShippingSyncer
     */
    private ShippingSyncer $shippingSyncer;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param ShippingSyncer $shippingSyncer
     */
    public function __construct(
        FBEHelper      $fbeHelper,
        ShippingSyncer $shippingSyncer
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->shippingSyncer = $shippingSyncer;
    }

    /**
     * Execute observer on FBE onboarding completion
     *
     * Immediately after onboarding we initiate full catalog sync.
     * It syncs all products and all categories to Meta Catalog
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $storeId = $observer->getEvent()->getStoreId();

        try {
            $this->shippingSyncer->syncShippingProfiles('meta_observer');
        } catch (Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'shipping_profiles_sync',
                    'event_type' => 'meta_observer'
                ]
            );
        }
    }
}
