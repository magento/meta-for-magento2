<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Tracker;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\Conversion\Model\Tracker\Purchase as PurchaseTracker;
use Meta\Conversion\Model\CapiTracker;

class Purchase implements ObserverInterface
{

    const EVENT_NAME = 'facebook_businessextension_ssapi_purchase';

    public function __construct(
        private readonly PurchaseTracker $purchaseTracker,
        private readonly CapiTracker $capiTracker
    ) { }

    public function execute(Observer $observer): void
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        $lastOrderId = !empty($orderIds) ? $orderIds[0] : null;
        if ($lastOrderId) {
            $payload = $this->purchaseTracker->getPayload(['lastOrder' => $lastOrderId]);
            $this->capiTracker->execute($payload, self::EVENT_NAME, $this->purchaseTracker->getEventType());
        }
    }
}
