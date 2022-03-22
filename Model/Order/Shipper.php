<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
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
        return $this->shippingHelper->getCarrierCodeForFacebook($track);
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

        $this->commerceHelper->setStoreId($storeId)
            ->markOrderAsShipped($fbOrderId, $itemsToShip, $trackingInfo);
        $shipment->getOrder()->addCommentToStatusHistory("Marked order as shipped on Facebook with {$track->getTitle()}. Tracking #: {$track->getNumber()}.");

        // @todo Update order totals
    }
}
