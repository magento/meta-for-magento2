<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Observer\Tracker\CustomerRegistrationSuccess;
use Magento\Store\Model\StoreManagerInterface;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\CustomerRegistrationSuccess as CustomerRegistrationSuccessTracker;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Customer\Model\Customer;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Directory\Model\Currency;

class CustomerRegistrationSuccessTest extends TestCase
{
    private $customerRegistrationSuccessTrackerMock;
    private $storeManagerMock;
    private $capiTrackerMock;
    private $subject;

    public function setUp(): void
    {
        $this->customerRegistrationSuccessTrackerMock = $this->getMockBuilder(CustomerRegistrationSuccessTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->capiTrackerMock = $this->getMockBuilder(CapiTracker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(CustomerRegistrationSuccess::class, [
            'customerRegistrationSuccessTracker' => $this->customerRegistrationSuccessTrackerMock,
            'storeManager' => $this->storeManagerMock,
            'capiTracker' => $this->capiTrackerMock
        ]);
    }

    public function testExecute()
    {
        $firstName = 'John';
        $lastName = 'Doe';
        $customerId = 1;
        $currency = 'USD';
        $payload = [
            'content_name' => $firstName . " " . $lastName,
            'value' => $customerId,
            'status' => "True",
            'currency' => $currency
        ];
        $eventType = 'Customer Registration';

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock = $this->getMockBuilder(Customer::class)
            ->addMethods(['getFirstname', 'getLastname'])
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->addMethods(['getCurrentCurrency'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $currencyMock = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $customerMock->expects($this->exactly(2))->method('getId')->willReturn($customerId);
        $customerMock->expects($this->once())->method('getFirstname')->willReturn($firstName);
        $customerMock->expects($this->once())->method('getLastname')->willReturn($lastName);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getCurrentCurrency')->willReturn($currencyMock);
        $currencyMock->expects($this->once())->method('getCode')->willReturn($currency);
        $this->customerRegistrationSuccessTrackerMock->expects($this->once())->method('getPayload')->with($payload)->willReturn($payload);
        $this->customerRegistrationSuccessTrackerMock->expects($this->once())->method('getEventType')->willReturn($eventType);
        $this->capiTrackerMock->expects($this->once())->method('execute')->with($payload, CustomerRegistrationSuccess::EVENT_NAME, $eventType, true);

        $this->subject->execute($observerMock);
    }
}
