<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Tracker;

use Magento\Framework\Event\ObserverInterface;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\AddToCart as AddToCartTracker;

class AddToCart implements ObserverInterface
{
    const EVENT_NAME = 'facebook_businessextension_ssapi_add_to_cart';

    public function __construct(
        private readonly AddToCartTracker $addToCartTracker,
        private readonly CapiTracker $capiTracker
    ) { }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        $payload = $this->addToCartTracker->getPayload(['productId' => $product->getId()]);
        $this->capiTracker->execute($payload, self::EVENT_NAME,  $this->addToCartTracker->getEventType());
    }
}
