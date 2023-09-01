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

use Magento\Framework\Exception\FileSystemException;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Filesystem;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Config\Model\Config;
use Exception;

class ShippingSettingsUpdatePlugin
{
    /**
     * @var ShippingDataFactory
     */
    protected ShippingDataFactory $shippingRatesFactory;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var Filesystem
     */
    protected Filesystem $fileSystem;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * Constructor for Shipping settings update plugin
     *
     * @param ShippingDataFactory $shippingRatesFactory
     * @param Filesystem $fileSystem
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ShippingDataFactory   $shippingRatesFactory,
        FileSystem            $fileSystem,
        GraphAPIAdapter       $graphApiAdapter,
        FBEHelper             $fbeHelper,
        SystemConfig          $systemConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->shippingRatesFactory = $shippingRatesFactory;
        $this->fileSystem = $fileSystem;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->storeManager = $storeManager;
    }

    /**
     *  This function is called whenever shipping settings are saved in Magento
     *
     * @param Interceptor $config
     * @throws FileSystemException
     */
    public function afterSave(Config $config): void
    {
        $section_name = $config->getSection();
        if ($section_name !== 'carriers') {
            return;
        }
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $shippingRates = $this->shippingRatesFactory->create();
                $fileBuilder = new ShippingFileBuilder($this->fileSystem);
                $shippingProfiles = [
                    $shippingRates->buildTableRatesProfile(),
                    $shippingRates->buildFlatRateProfile(),
                    $shippingRates->buildFreeShippingProfile()
                ];
                $file_uri = $fileBuilder->createFile($shippingProfiles);
                $partnerIntegrationId = $this->systemConfig->getCommercePartnerIntegrationId($store->getId());
                $this->graphApiAdapter->setDebugMode($this->systemConfig->isDebugMode($store->getId()))
                    ->setAccessToken($this->systemConfig->getAccessToken($store->getId()));
                $this->graphApiAdapter->uploadFile($partnerIntegrationId, $file_uri, "SHIPPING_PROFILES", "CREATE");
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
