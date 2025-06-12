<?php
declare(strict_types = 1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\Purchase;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Escaper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\Data\OrderAddressInterface;

class PurchaseTest extends TestCase
{
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $serverSideHelperMock;
    private $serverEventFactoryMock;
    private $customerMetadataMock;
    private $pricingHelperMock;
    private $orderRepositoryMock;
    private $orderMock;
    private $orderItemMock;
    private $orderAddressMock;
    private $escaperMock;
    private $subject;

    public function setUp(): void
    {
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serverSideHelperMock = $this->getMockBuilder(ServerSideHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serverEventFactoryMock = $this->getMockBuilder(ServerEventFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerMetadataMock = $this->getMockBuilder(CustomerMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->pricingHelperMock = $this->getMockBuilder(PricingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getId', 'getItems', 'getGrandTotal', 'getTotalQtyOrdered', 'getBillingAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderAddressMock = $this->getMockBuilder(OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Purchase::class, [
            'fbeHelper' => $this->fbeHelperMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'serverSideHelper' => $this->serverSideHelperMock,
            'serverEventFactory' => $this->serverEventFactoryMock,
            'customerMetadata' => $this->customerMetadataMock,
            'pricingHelper' => $this->pricingHelperMock,
            'orderRepository' => $this->orderRepositoryMock,
            'escaper' => $this->escaperMock
        ]);
    }

    public function testGetEventType()
    {
        $this->assertEquals('Purchase', $this->subject->getEventType());
    }

    public function testGetPayloadException()
    {
        $lastOrderId = 1;
        $params = [
            'lastOrder' => $lastOrderId
        ];
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($lastOrderId)
            ->willThrowException(new NoSuchEntityException(__('This order does not exist.')));

        $this->assertEquals([], $this->subject->getPayload($params));
    }

    public function testGetPayload()
    {
        $lastOrderId = 1;
        $params = [
            'lastOrder' => $lastOrderId
        ];
        $itemSku = 'test-sku';
        $itemName = 'test-name';
        $itemPrice = 10.00;
        $grandTotal = 20.00;
        $itemQty = 1;
        $currency  = 'USD';
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($lastOrderId)
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->exactly(2))
            ->method('getSku')
            ->willReturn($itemSku);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn($itemQty);
        $this->orderItemMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($itemPrice);
        $this->orderItemMock->expects($this->once())
            ->method('getName')
            ->willReturn($itemName);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);
        $this->orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($grandTotal);
        $this->pricingHelperMock->expects($this->once())
            ->method('currency')
            ->with($grandTotal, false, false)
            ->willReturn($grandTotal);
        $this->orderMock->expects($this->once())
            ->method('getTotalQtyOrdered')
            ->willReturn($itemQty);
        $this->orderMock->expects($this->once())
            ->method('getId')
            ->willReturn($lastOrderId);
        $this->orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($this->orderAddressMock);

        $this->subject->getPayload($params);
    }
}
