<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\AddPaymentInfo;

class AddPaymentInfoTest extends TestCase
{
    private $subject;
    
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(AddPaymentInfo::class);
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_add_payment_info', $this->subject->getEventToObserveName());
    }
}
