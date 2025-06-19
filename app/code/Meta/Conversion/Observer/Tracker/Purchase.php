<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Tracker;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\Conversion\Model\Tracker\Purchase as PurchaseTracker;
use Magento\Checkout\Model\Session as CheckoutSession;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\CapiEventIdHandler;

class Purchase implements ObserverInterface
{

    const EVENT_NAME = 'facebook_businessextension_ssapi_purchase';

    public function __construct(
        private readonly PurchaseTracker $purchaseTracker,
        private readonly CheckoutSession $checkoutSession,
        private readonly CapiTracker $capiTracker,
        private readonly CapiEventIdHandler $capiEventIdHandler
    ) { }

    public function execute(Observer $observer): void
    {
        if (!$this->capiEventIdHandler->getMetaEventId(self::EVENT_NAME)) {
            $lastOrderId = $this->checkoutSession->getLastRealOrder()->getEntityId();
            $payload = $this->purchaseTracker->getPayload(['lastOrder' => $lastOrderId]);
            $this->capiTracker->execute($payload, self::EVENT_NAME, $this->purchaseTracker->getEventType());
        }
    }
}
