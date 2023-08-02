<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerRegistrationSuccess implements ObserverInterface
{
    /**
     * @var Common
     */
    private Common $common;

    /**
     * Observer Constructor Customer Registration
     *
     * @param Common $common
     */
    public function __construct(
        Common $common
    ) {
        $this->common = $common;
    }

    /**
     * Execute action method for the Observer
     *
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($customer->getId()) {
            $customerData = [
                'content_name' => $customer->getFirstname() . " " . $customer->getLastname(),
                'value' => $customer->getId()
            ];
            $this->common->setCookieForMetaPixel('event_customer_register', $customerData);
        }
        return $this;
    }
}
