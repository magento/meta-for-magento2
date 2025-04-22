<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\CapiEventHandler;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class CapiEventHandlerTest extends TestCase
{
    private $serverSideHelperMock;
    private $fbeHelperMock;
    private $serverEventFactoryMock;
    private $jsonSerializerMock;
    private $subject;

    public function setUp(): void
    {
        $this->serverSideHelperMock = $this->getMockBuilder(ServerSideHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serverEventFactoryMock = $this->getMockBuilder(ServerEventFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonSerializerMock = $this->getMockBuilder(JsonSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $object = new ObjectManager($this);
        $this->subject = $object->getObject(CapiEventHandler::class, [
            'serverSideHelper' => $this->serverSideHelperMock,
            'fbeHelper' => $this->fbeHelperMock,
            'serverEventFactory' => $this->serverEventFactoryMock,
            'jsonSerializer' => $this->jsonSerializerMock
        ]);
    }

    public function testProcess()
    {
        $message = '{"event_id":"kjfabfkhba-afkbahb","event_type":"addtocart","event":"addtocart","sku":"test_sku","qty":1}';
        $payload = [
            'event_id' => 'kjfabfkhba-afkbahb',
            'event_type' => 'addtocart',
            'sku' => 'test_sku',
            'qty' => 1,
            'event' => 'addtocart',
        ];

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($message)
            ->willReturn($payload);

        $this->subject->process($message);
    }
}
