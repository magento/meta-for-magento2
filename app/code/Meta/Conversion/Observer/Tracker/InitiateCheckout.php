<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Tracker;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\Conversion\Model\Tracker\InitiateCheckout as InitiateCheckoutTracker;
use Magento\Checkout\Model\Session as CheckoutSession;
use Meta\Conversion\Model\CapiTracker;

class InitiateCheckout implements ObserverInterface
{

    const EVENT_NAME = 'facebook_businessextension_ssapi_initiate_checkout';

    public function __construct(
        private readonly InitiateCheckoutTracker $initiateCheckoutTracker,
        private readonly CheckoutSession $checkoutSession,
        private readonly CapiTracker $capiTracker
    ) { }

    public function execute(Observer $observer): void
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        $payload = $this->initiateCheckoutTracker->getPayload(['quoteId' => $quoteId]);
        $this->capiTracker->execute($payload, self::EVENT_NAME, $this->initiateCheckoutTracker->getEventType());
    }
}
