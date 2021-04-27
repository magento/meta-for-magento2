<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Order;

use Facebook\BusinessExtension\Helper\CommerceHelper;

use Facebook\BusinessExtension\Helper\ShippingHelper;
use Facebook\BusinessExtension\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
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
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return mixed
     */
    protected function getRetailerId($orderItem)
    {
        if ($this->systemConfig->getProductIdentifierAttr() === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $orderItem->getSku();
        } elseif ($this->systemConfig->getProductIdentifierAttr() === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
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
        $trackCarrierCode = strtoupper($track->getCarrierCode());
        $trackCarrierTitle = $track->getTitle();

        $supportedCarriers = $this->shippingHelper->getFbSupportedShippingCarriers();

        // First try to map Magento carrier code to FB carrier code
        if (array_key_exists($trackCarrierCode, $supportedCarriers)) {
            return $trackCarrierCode;
        }

        // Second try to map custom Magento carrier
        if ($trackCarrierCode === 'CUSTOM') {
            // Map Magento custom carrier title to FB carrier code and carrier title
            foreach ($supportedCarriers as $code => $title) {
                if (strcasecmp($trackCarrierTitle, $code) === 0 || strcasecmp($trackCarrierTitle, $title) === 0) {
                    return $code;
                }
            }
            // Try to map some US standard carriers
            $carriers = [
                'UPS'   => 'United Parcel Service',
                'USPS'  => 'United States Postal Service',
                'FEDEX' => 'Federal Express',
            ];
            foreach ($carriers as $code => $title) {
                if (stripos($trackCarrierTitle, $title) !== false) {
                    return $code;
                }
            }
        }

        // Finally return OTHER if we're unable to map
        return 'OTHER';
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $storeId = $shipment->getOrder()->getStoreId();

        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }

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

        $this->commerceHelper->setStoreId($storeId)
            ->markOrderAsShipped($fbOrderId, $itemsToShip, $trackingInfo);
        $shipment->getOrder()->addCommentToStatusHistory("Marked order as shipped on Facebook with {$track->getTitle()}. Tracking #: {$track->getNumber()}.");

        // @todo Update order totals
    }
}
