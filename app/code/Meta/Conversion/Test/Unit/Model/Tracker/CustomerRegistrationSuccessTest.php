<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\CustomerRegistrationSuccess;

class CustomerRegistrationSuccessTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(CustomerRegistrationSuccess::class);
    }

    public function testGetEventType()
    {
        $this->assertEquals('CompleteRegistration', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $param = [
            'content_name' => 'product',
            'currency' => 'USD',
            'value' => 10.00,
            'status' => true,
        ];

        $this->assertEquals($param, $this->subject->getPayload($param));
    }
}
