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

namespace Meta\Conversion\Observer;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\AAMSettingsFields;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerEventFactory;

use Meta\Conversion\Helper\ServerSideHelper;

class Purchase implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var ServerSideHelper
     */
    private ServerSideHelper $serverSideHelper;

    /**
     * @var ServerEventFactory
     */
    private ServerEventFactory $serverEventFactory;

    /**
     * @var CustomerMetadataInterface
     */
    private CustomerMetadataInterface $customerMetadata;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param ServerSideHelper $serverSideHelper
     * @param ServerEventFactory $serverEventFactory
     * @param CustomerMetadataInterface $customerMetadata
     * @param PricingHelper $pricingHelper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper,
        ServerEventFactory $serverEventFactory,
        CustomerMetadataInterface $customerMetadata,
        PricingHelper $pricingHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->serverSideHelper = $serverSideHelper;
        $this->serverEventFactory = $serverEventFactory;
        $this->customerMetadata = $customerMetadata;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * Execute action method for the Observer
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $order = $observer->getData('lastOrder');
            $customData = [
                'currency'     => $this->magentoDataHelper->getCurrency(),
                'value'        => $this->getOrderTotal($order),
                'content_type' => 'product',
                'content_ids'  => $this->getOrderContentIds($order),
                'contents'     => $this->getOrderContents($order),
                'order_id'     => (string)$this->getOrderId($order),
                'custom_properties' => [
                    'source'           => $this->fbeHelper->getSource(),
                    'pluginVersion'    => $this->fbeHelper->getPluginVersion()
                ]
            ];
            $event = $this->serverEventFactory->createEvent('Purchase', array_filter($customData), $eventId);
            $userDataFromOrder = $this->getUserDataFromOrder($order);
            $this->serverSideHelper->sendEvent($event, $userDataFromOrder);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }

    /**
     * Return all the match keys that can be extracted from order information
     *
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getUserDataFromOrder(OrderInterface $order): array
    {
        if (!$order) {
            return [];
        }

        $userData = [];

        $userData[AAMSettingsFields::EXTERNAL_ID] =
            $order->getCustomerId();
        $userData[AAMSettingsFields::EMAIL] =
            $this->magentoDataHelper->hashValue($order->getCustomerEmail());
        $userData[AAMSettingsFields::FIRST_NAME] =
            $this->magentoDataHelper->hashValue($order->getCustomerFirstname());
        $userData[AAMSettingsFields::LAST_NAME] =
            $this->magentoDataHelper->hashValue($order->getCustomerLastname());
        $userData[AAMSettingsFields::DATE_OF_BIRTH] =
            $this->magentoDataHelper->hashValue($order->getCustomerDob() ?? '');

        if ($order->getCustomerGender()) {
            $genderId = $order->getCustomerGender();
            $userData[AAMSettingsFields::GENDER] =
                $this->magentoDataHelper->hashValue(
                    $this->customerMetadata->getAttributeMetadata('gender')
                        ->getOptions()[$genderId]->getLabel()
                );
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $userData[AAMSettingsFields::ZIP_CODE] =
                $this->magentoDataHelper->hashValue($billingAddress->getPostcode());
            $userData[AAMSettingsFields::CITY] =
                $this->magentoDataHelper->hashValue($billingAddress->getCity());
            $userData[AAMSettingsFields::PHONE] =
                $this->magentoDataHelper->hashValue($billingAddress->getTelephone());
            $userData[AAMSettingsFields::STATE] =
                $this->magentoDataHelper->hashValue($billingAddress->getRegionCode());
            $userData[AAMSettingsFields::COUNTRY] =
                $this->magentoDataHelper->hashValue($billingAddress->getCountryId());
        }

        return array_filter($userData);
    }

    /**
     * Return the id of the last order
     *
     * @param OrderInterface $order
     * @return mixed|null
     */
    private function getOrderId(OrderInterface $order)
    {
        if (!$order) {
            return null;
        } else {
            return $order->getId();
        }
    }

    /**
     * Return information about the last order items
     *
     * @param OrderInterface $order
     * @link https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/custom-data/#contents
     *
     * @return array
     */
    private function getOrderContents(OrderInterface $order): array
    {
        if (!$order) {
            return [];
        }
        $contents = [];
        /** @var Item[] $items */
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $product = $item->getProduct();
            $contents[] = [
                'product_id' => $this->magentoDataHelper->getContentId($product),
                'quantity' => (int)$item->getQtyOrdered(),
                'item_price' => $item->getPrice(),
            ];
        }
        return $contents;
    }

    /**
     * Return the ids of the items in the last order
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getOrderContentIds(OrderInterface $order): array
    {
        if (!$order) {
            return [];
        }
        $contentIds = [];
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $contentIds[] = $this->magentoDataHelper->getContentId($item->getProduct());
        }
        return $contentIds;
    }

    /**
     * Return the last order total value
     *
     * @param OrderInterface $order
     * @return float|null
     */
    private function getOrderTotal(OrderInterface $order): ?float
    {
        if (!$order) {
            return null;
        }
        $subtotal = $order->getSubTotal();
        if ($subtotal) {
            return $this->pricingHelper->currency($subtotal, false, false);
        } else {
            return null;
        }
    }
}
