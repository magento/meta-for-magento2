<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer\Tracker;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\Purchase as PurchaseTracker;
use Meta\Conversion\Observer\Tracker\Purchase;
use PHPUnit\Framework\TestCase;

class PurchaseTest extends TestCase
{
    private $purchaseTrackerMock;
    private $checkoutSessionMock;
    private $capiTrackerMock;
    private $subject;

    public function setUp(): void
    {
        $this->purchaseTrackerMock = $this->getMockBuilder(PurchaseTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiTrackerMock = $this->getMockBuilder(CapiTracker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(Purchase::class, [
            'purchaseTracker' => $this->purchaseTrackerMock,
            'checkoutSession' => $this->checkoutSessionMock,
            'capiTracker' => $this->capiTrackerMock
        ]);
    }

    public function testExecute()
    {
        $eventType = 'Purchase';
        $orderId = 1;
        $payload = [
            'event' => $eventType,
            'orderId' => $orderId
        ];

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($orderMock);
        $orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($orderId);
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
