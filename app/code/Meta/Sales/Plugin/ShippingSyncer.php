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

use Exception;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class ShippingSyncer
{
    /**
     * @var ShippingFileBuilder
     */
    private ShippingFileBuilder $shippingFileBuilder;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var Filesystem
     */
    private Filesystem $fileSystem;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ShippingData
     */
    private ShippingData $shippingData;

    /**
     * Constructor for Shipping settings update plugin
     *
     * @param Filesystem $fileSystem
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param StoreManagerInterface $storeManager
     * @param ShippingFileBuilder $shippingFileBuilder
     * @param ShippingData $shippingData
     */
    public function __construct(
        FileSystem            $fileSystem,
        GraphAPIAdapter       $graphApiAdapter,
        FBEHelper             $fbeHelper,
        SystemConfig          $systemConfig,
        StoreManagerInterface $storeManager,
        ShippingFileBuilder   $shippingFileBuilder,
        ShippingData          $shippingData
    ) {
        $this->fileSystem = $fileSystem;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->storeManager = $storeManager;
        $this->shippingFileBuilder = $shippingFileBuilder;
        $this->shippingData = $shippingData;
    }

    /**
     * Syncing shipping profiles to Meta
     *
     * @param string|null $accessToken
     * @param string|null $partnerIntegrationId
     * @return void
     */
    public function syncShippingProfiles(string $accessToken = null, string $partnerIntegrationId = null)
    {
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $this->shippingData->setStoreId((int)$store->getId());
                $shippingProfiles = [
                    $this->shippingData->buildShippingProfile(ShippingProfileTypes::TABLE_RATE),
                    $this->shippingData->buildShippingProfile(ShippingProfileTypes::FLAT_RATE),
                    $this->shippingData->buildShippingProfile(ShippingProfileTypes::FREE_SHIPPING),
                ];
                $fileUri = $this->shippingFileBuilder->createFile($shippingProfiles);
                $accessToken = $accessToken ?? $this->systemConfig->getAccessToken($store->getId());
                $partnerIntegrationId = $partnerIntegrationId ??
                    $this->systemConfig->getCommercePartnerIntegrationId($store->getId());
                $this->graphApiAdapter->setDebugMode($this->systemConfig->isDebugMode($store->getId()))
                    ->setAccessToken($accessToken);
                $this->graphApiAdapter->uploadFile(
                    $partnerIntegrationId,
                    $fileUri,
                    'SHIPPING_PROFILES',
                    'CREATE'
                );
            } catch (Exception $e) {
                $this->fbeHelper->logExceptionImmediatelyToMeta($e, [
                    'store_id' => $this->fbeHelper->getStore()->getId(),
                    'event' => 'shipping_profile_sync',
                    'event_type' => 'after_save'
                ]);
            }
        }
    }
}
