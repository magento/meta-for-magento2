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

namespace Meta\Sales\Observer\Order;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Order\Shipper;
use Meta\Sales\Observer\MetaObserverTrait;
use Psr\Log\LoggerInterface;

class ShipmentObserver implements ObserverInterface
{
    use MetaObserverTrait;

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
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param Shipper $shipper
     * @param OrderHelper $orderHelper
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        SystemConfig    $systemConfig,
        LoggerInterface $logger,
        Shipper         $shipper,
        OrderHelper     $orderHelper,
        FBEHelper       $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->shipper = $shipper;
        $this->orderHelper = $orderHelper;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Get Exception Event
     *
     * @return string
     */
    protected function getExceptionEvent()
    {
        return 'shipment_observer_exception';
    }

    /**
     * Get Facebook Event Helper
     *
     * @return FBEHelper
     */
    protected function getFBEHelper()
    {
        return $this->fbeHelper;
    }

    /**
     * Get Store ID
     *
     * @param Observer $observer
     * @return string
     */
    protected function getStoreId(Observer $observer)
    {
        return (string)$this->getShipment($observer)->getOrder()->getStoreId();
    }

    /**
     * Get Shipment from Observer
     *
     * @param Observer $observer
     * @return Shipment|null
     */
    protected function getShipment(Observer $observer)
    {
        $event = $observer->getEvent()->getName();

        /** @var Shipment $shipment */
        if ($event == Shipper::MAGENTO_EVENT_SHIPMENT_SAVE_AFTER) {
            $shipment = $observer->getEvent()->getShipment();
        } elseif ($event == Shipper::MAGENTO_EVENT_TRACKING_SAVE_AFTER) {
            $shipment = $observer->getEvent()->getTrack()->getShipment();
        } else {
            $shipment = null;
        }

        return $shipment;
    }

    /**
     * Executor Implementation
     *
     * @param Observer $observer
     * @throws LocalizedException
     * @throws GuzzleException
     * @throws Exception
     */
    protected function executeImpl(Observer $observer)
    {
        $shipment = $this->getShipment($observer);
        $storeId = $shipment->getOrder()->getStoreId();
        if (!($this->systemConfig->isOrderSyncEnabled($storeId)
            && $this->systemConfig->isActiveExtension($storeId))) {
            return;
        }

        $this->executeAutoSync($shipment);
    }

    /**
     * Attempt to determine if a shipment needs to be synced or just updated
     *
     * @param Shipment $shipment
     * @throws LocalizedException
     * @throws GuzzleException
     * @throws Exception
     */
    private function executeAutoSync(Shipment $shipment)
    {
        $order = $shipment->getOrder();
        $magentoShipmentId = $shipment->getIncrementId();

        $this->orderHelper->setFacebookOrderExtensionAttributes($order, true);
        $fbOrderId = $order->getExtensionAttributes()->getFacebookOrderId();
        if (!$fbOrderId) {
            return;
        }

        $magentoShipmentId = $shipment->getIncrementId();
        if (array_key_exists($magentoShipmentId, $order->getExtensionAttributes()->getSyncedShipments())) {
            $this->executeUpdateShipmentTracking($shipment);
        } else {
            $this->executeMarkAsShipped($shipment);
        }
    }

    /**
     * Sync a new shipment
     *
     * @param Shipment $shipment
     * @throws LocalizedException
     * @throws GuzzleException
     * @throws Exception
     */
    private function executeMarkAsShipped(Shipment $shipment)
    {
        try {
            $this->shipper->markAsShipped($shipment);
        } catch (GuzzleException $e) {
            $response = $e->getResponse();
            $body = json_decode((string)$response->getBody());
            throw new LocalizedException(__(
                'Error code: "%1"; Error message: "%2"',
                (string)$body->error->code,
                (string)($body->error->error_user_msg ?? $body->error->message)
            ));
        }
    }

    /**
     * Sync an update to shipment tracking
     *
     * @param Shipment $shipment
     * @throws LocalizedException
     * @throws GuzzleException
     * @throws Exception
     */
    private function executeUpdateShipmentTracking(Shipment $shipment)
    {
        try {
            $this->shipper->updateShipmentTracking($shipment);
        } catch (GuzzleException $e) {
            $response = $e->getResponse();
            $body = json_decode((string)$response->getBody());

            // check if error was for order already having the same tracking info
            if ($body->error->error_subcode !== 2382045) {
                throw new LocalizedException(__(
                    'Error code: "%1"; Error message: "%2"',
                    (string)$body->error->code,
                    (string)($body->error->error_user_msg ?? $body->error->message)
                ));
            }
        }
    }
}
