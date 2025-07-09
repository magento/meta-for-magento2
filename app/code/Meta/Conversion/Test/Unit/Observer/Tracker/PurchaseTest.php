<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer\Tracker;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\Purchase as PurchaseTracker;
use Meta\Conversion\Observer\Tracker\Purchase;
use PHPUnit\Framework\TestCase;

class PurchaseTest extends TestCase
{
    private $purchaseTrackerMock;
    private $capiTrackerMock;
    private $subject;

    public function setUp(): void
    {
        $this->purchaseTrackerMock = $this->getMockBuilder(PurchaseTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiTrackerMock = $this->getMockBuilder(CapiTracker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(Purchase::class, [
            'purchaseTracker' => $this->purchaseTrackerMock,
            'capiTracker' => $this->capiTrackerMock
        ]);
    }

    public function testExecute()
    {
        $eventType = 'Purchase';
        $orderId = 1;
        $orderIds = [$orderId];
        $payload = [
            'event' => $eventType,
            'orderId' => $orderId
        ];

        $observerMock = $this->getMockBuilder(Observer::class)
            ->onlyMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrderIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);
        $eventMock->expects($this->once())
            ->method('getOrderIds')
            ->willReturn($orderIds);

        $this->purchaseTrackerMock->expects($this->once())
            ->method('getPayload')
            ->with(['lastOrder' => $orderId])
            ->willReturn($payload);
        $this->purchaseTrackerMock->expects($this->once())
            ->method('getEventType')
            ->willReturn($eventType);
        $this->capiTrackerMock->expects($this->once())
            ->method('execute')
            ->with($payload, Purchase::EVENT_NAME, $eventType);

        $this->subject->execute($observerMock);
    }
}
