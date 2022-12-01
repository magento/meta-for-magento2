<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Order;

use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Helper\ShippingHelper;
use Facebook\BusinessExtension\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Sales\Model\Order\Shipment\Track;
use Psr\Log\LoggerInterface;

class Shipper
{
    const MAGENTO_EVENT_SHIPMENT_SAVE_AFTER = 'sales_order_shipment_save_after';

    const MAGENTO_EVENT_TRACKING_SAVE_AFTER = 'sales_order_shipment_track_save_after';

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CommerceHelper
     */
    private $commerceHelper;

    /**
     * @var ShippingHelper
     */
    private $shippingHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param CommerceHelper $commerceHelper
     * @param ShippingHelper $shippingHelper
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        CommerceHelper $commerceHelper,
        ShippingHelper $shippingHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->commerceHelper = $commerceHelper;
        $this->shippingHelper = $shippingHelper;
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function getOrderShipEvent($storeId = null)
    {
        return $this->systemConfig->getOrderShipEvent($storeId);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return mixed
     */
    protected function getRetailerId($orderItem)
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
     * @param Track $track
     * @return string
     */
    public function getCarrierCodeForFacebook($track)
    {
        return $this->shippingHelper->getCarrierCodeForFacebook($track);
    }

    /**
     * @param $address
     * @throws LocalizedException
     */
    private function validateFulfillmentAddress($address)
    {
        $requiredFields = [
            'street_1'    => __('Street Address 1'),
            'country'     => __('Country'),
            'state'       => __('Region/State'),
            'city'        => __('City'),
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

        $this->commerceHelper->setStoreId($storeId)
            ->markOrderAsShipped($fbOrderId, $itemsToShip, $trackingInfo, $fulfillmentAddress);
        $shipment->getOrder()->addCommentToStatusHistory("Marked order as shipped on Facebook with {$track->getTitle()}. Tracking #: {$track->getNumber()}.");

        // @todo Update order totals
    }
}
