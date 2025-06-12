<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Controller\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Controller\Pixel\Tracker;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Meta\Conversion\Model\Tracker\AddToWishlist;

class TrackerTest extends TestCase
{
    private $requestMock;
    private $fbeHelperMock;
    private $jsonFactoryMock;
    private $jsonMock;
    private $publisherMock;
    private $jsonSerializerMock;
    private $addToWishlistMock;
    private $subject;

    public function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonSerializerMock = $this->getMockBuilder(JsonSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addToWishlistMock = $this->getMockBuilder(AddToWishlist::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Tracker::class, [
            'request' => $this->requestMock,
            'fbeHelper' => $this->fbeHelperMock,
            'jsonFactory' => $this->jsonFactoryMock,
            'publisher' => $this->publisherMock,
            'jsonSerializer' => $this->jsonSerializerMock,
            'pixelEvents' => [
                'addToWishlist' => $this->addToWishlistMock
            ]
        ]);
    }

    public function testExecute()
    {
        $eventName = 'addToWishlist';
        $eventId = '12345-2345-sdfg-2345';
        $param = [
            'eventName' => $eventName,
            'eventId' => $eventId
        ];
        $payload = [
            'sku' => 'random-sku'
        ];
        $finalPayload = [
            'sku' => 'random-sku',
            'event_id' => $eventId,
            'event_type' => $eventName
        ];
        $response = ['success' => true];

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($param);
        $this->addToWishlistMock->expects($this->once())
            ->method('getPayload')
            ->with($param)
            ->willReturn($payload);
        $this->addToWishlistMock->expects($this->once())
            ->method('getEventType')
            ->willReturn($eventName);
        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($finalPayload)
            ->willReturn(json_encode($finalPayload));
        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('send.conversion.event.to.meta', json_encode($finalPayload));
        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->with($response)
            ->willReturnSelf();

        $this->subject->execute();
    }
}
