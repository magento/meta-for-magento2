<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\ViewContact;

class ViewContactTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(ViewContact::class);
    }

    public function testGetContentType()
    {
        $this->assertEquals('contact', $this->subject->getContentType());
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_contact', $this->subject->getEventToObserveName());
    }
}
