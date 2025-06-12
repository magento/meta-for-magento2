<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer;

use Magento\Customer\Model\Customer;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Observer\CustomerRegistrationSuccess;
use Meta\Conversion\Observer\Common;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;

class CustomerRegistrationSuccessTest extends TestCase
{
    private $commonMock;
    private $subject;

    public function setUp(): void
    {
        $this->commonMock = $this->getMockBuilder(Common::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(CustomerRegistrationSuccess::class, ['common' => $this->commonMock]);
    }

    public function testExecute(): void
    {
        $firstName = 'John';
        $lastName = 'Doe';
        $customerId = 1;

        $customerData = [
            'content_name' => $firstName . " " . $lastName,
            'value' => $customerId,
            'status' => "True"
        ];

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

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $customerMock->expects($this->exactly(2))->method('getId')->willReturn($customerId);
        $customerMock->expects($this->once())->method('getFirstname')->willReturn($firstName);
        $customerMock->expects($this->once())->method('getLastname')->willReturn($lastName);
        $this->commonMock->expects($this->once())
            ->method('setCookieForMetaPixel')
            ->with('event_customer_register', $customerData);

        $this->subject->execute($observerMock);
    }
}
