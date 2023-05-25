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

namespace Meta\Sales\Observer\Order;

use Meta\Sales\Model\Order\Shipper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
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
     * Constructor
     *
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
        } elseif ($event == Shipper::MAGENTO_EVENT_TRACKING_SAVE_AFTER) {
            $shipment = $observer->getEvent()->getTrack()->getShipment();
        } else {
            return;
        }

        $storeId = $shipment->getOrder()->getStoreId();
        if ($event !== $this->shipper->getOrderShipEvent($storeId)) {
            return;
        }

        if (!($this->systemConfig->isActiveExtension($storeId)
            && $this->systemConfig->isActiveOrderSync($storeId)
            && $this->systemConfig->isOnsiteCheckoutEnabled($storeId))) {
            return;
        }

        $this->shipper->markAsShipped($shipment);
    }
}
