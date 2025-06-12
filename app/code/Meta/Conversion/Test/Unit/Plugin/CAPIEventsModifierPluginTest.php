<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Plugin\CAPIEventsModifierPlugin;
use FacebookAds\Object\ServerSide\Event;
use Meta\Conversion\Helper\ServerSideHelper;

class CAPIEventsModifierPluginTest extends TestCase
{
    private $eventMock;
    private $serverSideHelperMock;
    private $subject;

    public function setUp(): void
    {
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serverSideHelperMock = $this->getMockBuilder(ServerSideHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(CAPIEventsModifierPlugin::class);
    }

    public function testBeforeSendEventWithNullData()
    {
        $this->assertEquals([$this->eventMock, null], $this->subject->beforeSendEvent($this->serverSideHelperMock, $this->eventMock));
    }

    public function testBeforeSendEvent()
    {
        $userData = [
            'name' => 'John Doe'
        ];
        $this->assertEquals([$this->eventMock, $userData], $this->subject->beforeSendEvent($this->serverSideHelperMock, $this->eventMock, $userData));
    }

}
