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

namespace Meta\Sales\Model\Order;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Sales\Model\Order\Shipment\Track;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Meta\Sales\Helper\ShippingHelper;

class Shipper
{
    public const MAGENTO_EVENT_SHIPMENT_SAVE_AFTER = 'sales_order_shipment_save_after';

    public const MAGENTO_EVENT_TRACKING_SAVE_AFTER = 'sales_order_shipment_track_save_after';

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var ShippingHelper
     */
    private ShippingHelper $shippingHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param ShippingHelper $shippingHelper
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphAPIAdapter,
        ShippingHelper $shippingHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->shippingHelper = $shippingHelper;
    }

    /**
     * Get order ship event
     *
     * @param int|null $storeId
     * @return null|string
     */
    public function getOrderShipEvent($storeId = null)
    {
        return $this->systemConfig->getOrderShipEvent($storeId);
    }

    /**
     * Get retailer id
     *
     * @param OrderItem $orderItem
     * @return string|int|bool
     */
    protected function getRetailerId(OrderItem $orderItem)
    {
        $storeId = $orderItem->getStoreId();
        $productIdentifierAttr = $this->systemConfig->getProductIdentifierAttr($storeId);
        if ($productIdentifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $orderItem->getSku();
        } elseif ($productIdentifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $orderItem->getProductId();
        }
        return false;
    }

    /**
     * Get carrier code for facebook
     *
     * @param Track $track
     * @return string
     */
    public function getCarrierCodeForFacebook(Track $track): string
    {
        return $this->shippingHelper->getCarrierCodeForFacebook($track);
    }

    /**
     * Validate fulfillment address
     *
     * @param array $address
     * @return void
     * @throws LocalizedException
     */
    private function validateFulfillmentAddress(array $address)
    {
        $requiredFields = [
            'street_1' => __('Street Address 1'),
            'country' => __('Country'),
            'state' => __('Region/State'),
            'city' => __('City'),
            'postal_code' => __('Zip/Postal Code')
        ];
        $missingFields = array_filter($requiredFields, function ($field) use ($address) {
            return empty($address[$field]);
        }, ARRAY_FILTER_USE_KEY);
        if (!empty($missingFields)) {
            throw new LocalizedException(__(
                'Please provide the required fields: %1 in the Fulfillment Address section.',
                implode(', ', array_values($missingFields))
            ));
        }
    }

    /**
     * Mark facebook order as shipped
     *
     * @param Shipment $shipment
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function markAsShipped(Shipment $shipment)
    {
        $storeId = $shipment->getOrder()->getStoreId();
        $fbOrderId = $shipment->getOrder()->getExtensionAttributes()->getFacebookOrderId();
        if (!$fbOrderId) {
            return;
        }

        $itemsToShip = [];
        /** @var Item $shipmentItem */
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            $itemsToShip[] = [
                'retailer_id' => $this->getRetailerId($orderItem),
                'quantity' => (int)$shipmentItem->getQty()
            ];
        }

        $tracks = $shipment->getAllTracks();
        if (count($tracks) == 0) {
            throw new LocalizedException(__('Please provide a tracking number.'));
        }
        if (count($tracks) > 1) {
            throw new LocalizedException(__('Please provide only one tracking number per shipment.'));
        }

        /** @var Track $track */
        $track = $tracks[0];

        $trackingInfo = [
            'tracking_number' => $track->getNumber(),
            'shipping_method_name' => $track->getTitle(),
            'carrier' => $this->getCarrierCodeForFacebook($track),
        ];

        $fulfillmentAddress = [];
        if (!$this->systemConfig->shouldUseDefaultFulfillmentAddress($storeId)) {
            $fulfillmentAddress = $this->systemConfig->getFulfillmentAddress($storeId);
            $this->validateFulfillmentAddress($fulfillmentAddress);
            $fulfillmentAddress['state'] = $this->shippingHelper->getRegionName($fulfillmentAddress['state']);
        }

        $this->markOrderAsShipped(
            (int)$storeId,
            $fbOrderId,
            $itemsToShip,
            $trackingInfo,
            $fulfillmentAddress
        );

        $comment = "Marked order as shipped on Facebook with {$track->getTitle()}. Tracking #: {$track->getNumber()}.";
        $shipment->getOrder()->addCommentToStatusHistory($comment);

        // @todo Update order totals
    }

    /**
     * Mark a facebook order as shipped
     *
     * @param int $storeId
     * @param string $fbOrderId
     * @param array $items
     * @param array $trackingInfo
     * @param array $fulfillmentAddressData
     * @throws GuzzleException
     */
    private function markOrderAsShipped(
        int $storeId,
        string $fbOrderId,
        array $items,
        array $trackingInfo,
        array $fulfillmentAddressData = []
    ) {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $this->graphAPIAdapter->markOrderAsShipped(
            $fbOrderId,
            $items,
            $trackingInfo,
            $fulfillmentAddressData
        );
    }
}
