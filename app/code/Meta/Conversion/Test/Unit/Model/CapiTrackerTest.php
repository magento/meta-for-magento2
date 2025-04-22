<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\CapiTracker;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Meta\Conversion\Model\CapiEventIdHandler;

class CapiTrackerTest extends TestCase
{
    private $customerSessionMock;
    private $capiEventIdHandlerMock;
    private $jsonSerializerMock;
    private $publisherMock;
    private $object;
    private $subject;

    public function setUp(): void
    {
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiEventIdHandlerMock = $this->getMockBuilder(CapiEventIdHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonSerializerMock = $this->getMockBuilder(JsonSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(CapiTracker::class, [
            'customerSession' => $this->customerSessionMock,
            'capiEventIdHandler' => $this->capiEventIdHandlerMock,
            'jsonSerializer' => $this->jsonSerializerMock,
            'publisher' => $this->publisherMock
        ]);
    }

    public function testExecute()
    {
        $payload = [
            'event' => 'addtocart',
            'sku' => 'test_sku',
            'qty' => 1
        ];

        $this->subject->execute($payload, 'addtocart', 'addtocart', true);

    }
}
