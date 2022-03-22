<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Order;

use Facebook\BusinessExtension\Model\Order\Shipper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
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
     * @var Shipper
     */
    private $shipper;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param Shipper $shipper
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        Shipper $shipper
    ) {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->shipper = $shipper;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent()->getName();

        /** @var Shipment $shipment */
        if ($event == Shipper::MAGENTO_EVENT_SHIPMENT_SAVE_AFTER) {
            $shipment = $observer->getEvent()->getShipment();
        } else if ($event == Shipper::MAGENTO_EVENT_TRACKING_SAVE_AFTER) {
            $shipment = $observer->getEvent()->getTrack()->getShipment();
        } else {
            return;
        }

        $storeId = $shipment->getOrder()->getStoreId();
        if ($event !== $this->shipper->getOrderShipEvent($storeId)) {
            return;
        }

        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }

        $this->shipper->markAsShipped($shipment);
    }
}
