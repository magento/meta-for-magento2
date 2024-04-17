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

namespace Meta\Sales\Model\Order;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Sales\Model\Order\Shipment\Track;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Helper\ShippingHelper;
use Meta\Sales\Model\FacebookOrder;

class Shipper
{

    public const MAGENTO_EVENT_SHIPMENT_AUTO = 'auto';
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
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var FacebookOrder
     */
    private FacebookOrder $facebookOrder;

    /**
     * @param SystemConfig    $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param ShippingHelper  $shippingHelper
     * @param OrderHelper     $orderHelper
     * @param FBEHelper       $fbeHelper
     * @param FacebookOrder   $facebookOrder
     */
    public function __construct(
        SystemConfig    $systemConfig,
        GraphAPIAdapter $graphAPIAdapter,
        ShippingHelper  $shippingHelper,
        OrderHelper     $orderHelper,
        FBEHelper       $fbeHelper,
        FacebookOrder   $facebookOrder
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->shippingHelper = $shippingHelper;
        $this->orderHelper = $orderHelper;
        $this->fbeHelper = $fbeHelper;
        $this->facebookOrder = $facebookOrder;
    }

    /**
     * Mark order items as shipped
     *
     * @param  Shipment $shipment
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function markAsShipped(Shipment $shipment)
    {
        $order = $shipment->getOrder();

        $this->orderHelper->setFacebookOrderExtensionAttributes($order);
        $fbOrderId = $order->getExtensionAttributes()->getFacebookOrderId();
        if (!$fbOrderId) {
            return;
        }

        $tracks = $shipment->getAllTracks();
        $track = null;
        if (count($tracks) == 0) {
            // Allow fulfillment without tracking
            $trackingInfo = [
                'tracking_number' => '',
                'carrier' => 'OTHER',
            ];
        } else {
            /**
 * @var Track $track 
*/
            $track = $tracks[0];

            $trackingInfo = [
                'tracking_number' => $track->getNumber(),
                'shipping_method_name' => $track->getTitle(),
                'carrier' => $this->getCarrierCodeForFacebook($track),
            ];
        }

        $magentoShipmentId = $shipment->getIncrementId();
        if (!$this->facebookOrder->isSyncedShipmentOutOfSync($order, $magentoShipmentId, $trackingInfo)) {
            $this->fbeHelper->log("[markAsShipped] Shipment: {$shipment->getIncrementId()} - Skipping, in sync");
            return;
        }

        $storeId = $order->getStoreId();

        $itemsToShipBySku = [];
        $itemsToShipById = [];
        /**
 * @var Item $shipmentItem 
*/
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            $itemsToShipBySku[] = [
                'retailer_id' => $orderItem->getSku(),
                'quantity' => (int)$shipmentItem->getQty()
            ];
            $itemsToShipById[] = [
                'retailer_id' => $orderItem->getId(),
                'quantity' => (int)$shipmentItem->getQty()
            ];
        }

        $fulfillmentAddress = [];
        if (!$this->systemConfig->shouldUseDefaultFulfillmentAddress($storeId)) {
            $fulfillmentAddress = $this->systemConfig->getFulfillmentAddress($storeId);
            $this->validateFulfillmentAddress($fulfillmentAddress);
            $fulfillmentAddress['state'] = $this->shippingHelper->getRegionName($fulfillmentAddress['state']);
        }

        try {
            $this->markOrderItemsAsShipped(
                (int)$storeId,
                $fbOrderId,
                $shipment->getIncrementId(),
                $itemsToShipBySku,
                $trackingInfo,
                $fulfillmentAddress
            );
        } catch (Exception $e) {
            // Validated the Meta API will throw if retailer ids provided are invalid
            // https://fburl.com/code/p523l7gm
            $this->markOrderItemsAsShipped(
                (int)$storeId,
                $fbOrderId,
                $shipment->getIncrementId(),
                $itemsToShipById,
                $trackingInfo,
                $fulfillmentAddress
            );
        }

        if ($track) {
            $comment = "Order Marked as Shipped on Meta for {$track->getTitle()}. Tracking #: {$track->getNumber()}";
        } else {
            $comment = "Order Marked as Shipped on Meta";
        }

        $order->addCommentToStatusHistory($comment)->save();

        $fbOrder = $this->orderHelper->loadFacebookOrderFromMagentoId($order->getId());
        $fbOrder->updateSyncedShipment($magentoShipmentId, $trackingInfo)->save();

        // @todo Update order totals
    }

    /**
     * Get carrier code for facebook
     *
     * @param  Track $track
     * @return string
     */
    public function getCarrierCodeForFacebook(Track $track): string
    {
        return $this->shippingHelper->getCarrierCodeForFacebook($track);
    }

    /**
     * Validate fulfillment address
     *
     * @param  array $address
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
        $missingFields = array_filter(
            $requiredFields, function ($field) use ($address) {
                return empty($address[$field]);
            }, ARRAY_FILTER_USE_KEY
        );
        if (!empty($missingFields)) {
            throw new LocalizedException(
                __(
                    'Please provide the required fields: %1 in the Fulfillment Address section.',
                    implode(', ', array_values($missingFields))
                )
            );
        }
    }

    /**
     * Mark facebook order items as shipped
     *
     * @param  int    $storeId
     * @param  string $fbOrderId
     * @param  string $magentoShipmentId
     * @param  array  $items
     * @param  array  $trackingInfo
     * @param  array  $fulfillmentAddressData
     * @throws GuzzleException
     */
    private function markOrderItemsAsShipped(
        int    $storeId,
        string $fbOrderId,
        string $magentoShipmentId,
        array  $items,
        array  $trackingInfo,
        array  $fulfillmentAddressData = []
    ) {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $this->graphAPIAdapter->markOrderItemsAsShipped(
            $fbOrderId,
            $magentoShipmentId,
            $items,
            $trackingInfo,
            $fulfillmentAddressData
        );
    }

    /**
     * Update shipment tracking info
     *
     * @param  Shipment $shipment
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function updateShipmentTracking(Shipment $shipment)
    {
        $order = $shipment->getOrder();

        $this->orderHelper->setFacebookOrderExtensionAttributes($order);
        $fbOrderId = $order->getExtensionAttributes()->getFacebookOrderId();
        if (!$fbOrderId) {
            return;
        }

        $tracks = $shipment->getAllTracks();

        if (count($tracks) == 0) {
            // For now, we don't support removing tracking entirely
            $this->fbeHelper->log(
                "[updateShipmentTracking] Shipment: {$shipment->getIncrementId()} - Skipping, no tracks"
            );
            return;
        }

        /**
 * @var Track $track 
*/
        $track = $tracks[0];

        $trackingInfo = [
            'tracking_number' => $track->getNumber(),
            'shipping_method_name' => $track->getTitle(),
            'carrier' => $this->getCarrierCodeForFacebook($track),
        ];

        $magentoShipmentId = $shipment->getIncrementId();
        if (!$this->facebookOrder->isSyncedShipmentOutOfSync($order, $magentoShipmentId, $trackingInfo)) {
            $this->fbeHelper->log(
                "[updateShipmentTracking] Shipment: {$shipment->getIncrementId()} - Skipping, in sync"
            );
            return;
        }

        $this->updateOrderShipmentTracking(
            (int)$order->getStoreId(),
            $fbOrderId,
            $magentoShipmentId,
            $trackingInfo,
        );

        $comment = "Order Shipment Tracking Updated on Meta for {$track->getTitle()}. Tracking #{$track->getNumber()}";
        $order->addCommentToStatusHistory($comment)->save();

        $fbOrder = $this->orderHelper->loadFacebookOrderFromMagentoId($order->getId());
        $fbOrder->updateSyncedShipment($magentoShipmentId, $trackingInfo)->save();
    }

    /**
     * Update shipment tracking info
     *
     * @param  int    $storeId
     * @param  string $fbOrderId
     * @param  string $magentoShipmentId
     * @param  array  $trackingInfo
     * @throws GuzzleException
     */
    private function updateOrderShipmentTracking(
        int    $storeId,
        string $fbOrderId,
        string $magentoShipmentId,
        array  $trackingInfo
    ) {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $this->graphAPIAdapter->updateShipmentTracking(
            $fbOrderId,
            $magentoShipmentId,
            $trackingInfo,
        );
    }
}
