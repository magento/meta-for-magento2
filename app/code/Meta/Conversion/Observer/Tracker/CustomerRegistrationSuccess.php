<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Tracker;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\CustomerRegistrationSuccess as CustomerRegistrationSuccessTracker;

class CustomerRegistrationSuccess implements ObserverInterface
{

    const EVENT_NAME = 'facebook_businessextension_ssapi_customer_registration_success';

    public function __construct(
        private CustomerRegistrationSuccessTracker $customerRegistrationSuccessTracker,
        private StoreManagerInterface $storeManager,
        private readonly CapiTracker $capiTracker
    ) { }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        if ($initialPayload = $this->getInitialPayload($customer)) {
            $payload = $this->customerRegistrationSuccessTracker->getPayload($initialPayload);
            $this->capiTracker->execute($payload, self::EVENT_NAME, $this->customerRegistrationSuccessTracker->getEventType());
        }
    }

    private function getInitialPayload($customer)
    {
        if ($customer->getId()) {
            return  [
                'content_name' => $customer->getFirstname() . " " . $customer->getLastname(),
                'value' => $customer->getId(),
                'status' => "True",
                'currency' => $this->storeManager->getStore()->getCurrentCurrency()->getCode()
            ];
        }
        return false;
    }
}
