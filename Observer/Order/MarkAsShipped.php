<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Order;

use Facebook\BusinessExtension\Helper\CommerceHelper;

use Facebook\BusinessExtension\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Sales\Model\Order\Shipment\Track;
use Psr\Log\LoggerInterface;

class MarkAsShipped implements ObserverInterface
{
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
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param CommerceHelper $commerceHelper
     */
    public function __construct(SystemConfig $systemConfig, LoggerInterface $logger, CommerceHelper $commerceHelper)
    {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->commerceHelper = $commerceHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return mixed
     */
    protected function getRetailerId($orderItem)
    {
        if ($this->systemConfig->getProductIdentifierAttr() === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $orderItem->getSku();
        } else if ($this->systemConfig->getProductIdentifierAttr() === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $orderItem->getProductId();
        }
        return false;
    }

    /**
     * @param Track $track
     * @return mixed
     * @throws LocalizedException
     */
    public function getCarrierCodeForFacebook($track)
    {
        // @todo Implement all carrier codes https://developers.facebook.com/docs/commerce-platform/order-management/carrier-codes
        $carrierCodesMap = [
            \Magento\Fedex\Model\Carrier::CODE => 'FEDEX',
            \Magento\Ups\Model\Carrier::CODE   => 'UPS',
            \Magento\Usps\Model\Carrier::CODE  => 'USPS',
            \Magento\Dhl\Model\Carrier::CODE   => 'DHL',
            'United Parcel Service'            => 'UPS',
            'United States Postal Service'     => 'USPS',
            'Federal Express'                  => 'FEDEX',
        ];

        // Try to map using carrier title for custom carrier
        if ($track->getCarrierCode() === 'custom') {
            foreach ($carrierCodesMap as $magentoCarrierCode => $facebookCarrierCode) {
                if (stripos($track->getTitle(), $magentoCarrierCode) !== false) {
                    return $facebookCarrierCode;
                }
            }
            throw new LocalizedException(__(sprintf('Cannot map custom carrier. Create a plugin for Facebook\BusinessExtension\Observer\Order\MarkAsShipped::execute() for your custom mapping.')));
        }

        if (!array_key_exists($track->getCarrierCode(), $carrierCodesMap)) {
            throw new LocalizedException(__(sprintf('Carrier "%s" is not supported by Facebook.', $track->getCarrierCode())));
        }

        return $carrierCodesMap[$track->getCarrierCode()];
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveOrderSync())) {
            return;
        }

        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if (!$shipment->getOrder()->getExtensionAttributes()->getFacebookOrderId()) {
            return;
        }

        $itemsToShip = [];
        /** @var Item $shipmentItem */
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            $itemsToShip[] = ['retailer_id' => $this->getRetailerId($orderItem), 'quantity' => (int)$shipmentItem->getQty()];
        }

        $fbOrderId = $shipment->getOrder()->getExtensionAttributes()->getFacebookOrderId();

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

        $this->commerceHelper->markOrderAsShipped($fbOrderId, $itemsToShip, $trackingInfo);
        $shipment->getOrder()->addCommentToStatusHistory("Marked order as shipped on Facebook with {$track->getTitle()}. Tracking #: {$track->getNumber()}.");

        // @todo Update order totals
    }
}
