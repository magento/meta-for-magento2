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

namespace Meta\Sales\Helper;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Api\Data\FacebookOrderInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

class OrderHelper
{
    /**
     * @var OrderExtensionFactory
     */
    private OrderExtensionFactory $orderExtensionFactory;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private FacebookOrderInterfaceFactory $facebookOrderFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param OrderExtensionFactory         $orderExtensionFactory
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param CollectionFactory             $collectionFactory
     * @param StoreManagerInterface         $storeManager
     */
    public function __construct(
        OrderExtensionFactory         $orderExtensionFactory,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        CollectionFactory             $collectionFactory,
        StoreManagerInterface         $storeManager
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Assign Meta order's extension attributes such as facebook_order_id to a Magento order
     *
     * @param  mixed $magentoOrderId
     * @return FacebookOrderInterface
     */
    public function loadFacebookOrderFromMagentoId($magentoOrderId): FacebookOrderInterface
    {
        /**
 * @var FacebookOrderInterface $facebookOrder 
*/
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($magentoOrderId, 'magento_order_id');

        return $facebookOrder;
    }

    /**
     * Assign Meta order's extension attributes such as facebook_order_id to a Magento order
     *
     * @param  OrderInterface $order
     * @param  bool           $reload
     * @return void
     */
    public function setFacebookOrderExtensionAttributes(OrderInterface $order, bool $reload = false)
    {
        // if FB order ID present, do nothing
        if ($order->getExtensionAttributes()->getFacebookOrderId() && !$reload) {
            return;
        }

        $facebookOrder = $this->loadFacebookOrderFromMagentoId($order->getId());

        if (!$facebookOrder->getId()) {
            return;
        }

        $emailRemarketingOption = ($facebookOrder->getExtraData()['email_remarketing_option'] ?? false) === true;
        $syncedShipments = $facebookOrder->getSyncedShipments();

        $extensionAttributes = $order->getExtensionAttributes() ?: $this->orderExtensionFactory->create();
        $extensionAttributes->setFacebookOrderId($facebookOrder->getFacebookOrderId())
            ->setChannel($facebookOrder->getChannel())
            ->setEmailRemarketingOption($emailRemarketingOption)
            ->setSyncedShipments($syncedShipments);
        $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get storeId from externalBusinessId
     *
     * @param  string $externalBusinessId
     * @return string
     * @throws LocalizedException
     */
    public function getStoreIdByExternalBusinessId(string $externalBusinessId): string
    {
        $installedConfigs = $this->getMBEInstalledConfigsByExternalBusinessId($externalBusinessId);
        if (empty($installedConfigs)) {
            throw new LocalizedException(
                __(
                    'No store id was found for external_business_id: '.$externalBusinessId
                )
            );
        }
        return $installedConfigs[0]->getScopeId();
    }

    /**
     * Get configs where MBE is installed for $externalBusinessId
     *
     * @param  string $externalBusinessId
     * @return array
     */
    public function getMBEInstalledConfigsByExternalBusinessId(string $externalBusinessId): array
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection
                ->addFieldToFilter('scope', ['eq' => 'stores'])
                ->addFieldToFilter(
                    'path',
                    ['eq' => SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID]
                )
                ->addValueFilter($externalBusinessId)
                ->addFieldToSelect('scope_id');
            return $collection->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get website ID from store ID
     *
     * @param  int $storeId
     * @return int
     */
    public function getWebsiteIdFromStoreId(int $storeId): int
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            return (int)$store->getWebsiteId();
        } catch (\Exception $e) {
            throw new LocalizedException(__("Unable to find website for store ID: %1", $storeId));
        }
    }
}
