<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\Purchase;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Meta\Conversion\Model\CapiEventIdHandler;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Catalog\Model\Product;

class PurchaseTest extends TestCase
{
    private $contextMock;
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $systemConfigMock;
    private $escaperMock;
    private $checkoutSessionMock;
    private $orderMock;
    private $orderItemMock;
    private $capiEventIdHandlerMock;
    private $subject;

    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiEventIdHandlerMock = $this->getMockBuilder(CapiEventIdHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Purchase::class, [
            'context' => $this->contextMock,
            'fbeHelper' => $this->fbeHelperMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'escaper' => $this->escaperMock,
            'checkoutSession' => $this->checkoutSessionMock,
            'capiEventIdHandler' => $this->capiEventIdHandlerMock
        ]);
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_purchase', $this->subject->getEventToObserveName());
    }

    public function testGetContentIDs()
    {
        $productSku = 'test_product_sku';
        $this->fbeHelperMock->expects($this->once())
            ->method('getObject')
            ->with(CheckoutSession::class)
            ->willReturn($this->checkoutSessionMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);
        $this->assertEquals([$productSku], $this->subject->getContentIDs());
    }

    public function testGetValueNoOrder()
    {
        $this->fbeHelperMock->expects($this->once())
            ->method('getObject')
            ->with(CheckoutSession::class)
            ->willReturn($this->checkoutSessionMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn(null);
        $this->assertNull($this->subject->getValue());
    }

    public function testGetValueNoSubtotal()
    {
        $this->fbeHelperMock->expects($this->once())
            ->method('getObject')
            ->with(CheckoutSession::class)
            ->willReturn($this->checkoutSessionMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(null);
        $this->assertNull($this->subject->getValue());
    }

    public function testGetValue()
    {
        $subtotal = 100;
        $priceHelperMock = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fbeHelperMock->expects($this->exactly(2))
            ->method('getObject')
            ->withConsecutive([CheckoutSession::class], [PriceHelper::class])
            ->willReturnOnConsecutiveCalls($this->checkoutSessionMock, $priceHelperMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($subtotal);
        $priceHelperMock->expects($this->once())
            ->method('currency')
            ->with($subtotal, false, false)
            ->willReturn($subtotal);

        $this->assertEquals($subtotal, $this->subject->getValue());
    }

    public function testGetContents()
    {
        $productSku = 'test_product_sku';
        $productQty = 1;
        $productPrice = 100;
        $contents = [
            [
                'id' => $productSku,
                'quantity' => $productQty,
                'item_price' => $productPrice
            ]
        ];
        $priceHelperMock = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fbeHelperMock->expects($this->exactly(2))
            ->method('getObject')
            ->withConsecutive([CheckoutSession::class], [PriceHelper::class])
            ->willReturnOnConsecutiveCalls($this->checkoutSessionMock, $priceHelperMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);
        $productMock->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn($productPrice);
        $priceHelperMock->expects($this->once())
            ->method('currency')
            ->with($productPrice, false, false)
            ->willReturn($productPrice);
        $this->orderItemMock->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn($productQty);

        $this->assertEquals($contents, $this->subject->getContents());
    }

    public function testGetNumItems()
    {
        $totalQtyOrdered = 1;
        $this->fbeHelperMock->expects($this->once())
            ->method('getObject')
            ->with(CheckoutSession::class)
            ->willReturn($this->checkoutSessionMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getTotalQtyOrdered')
            ->willReturn($totalQtyOrdered);
        $this->assertEquals($totalQtyOrdered, $this->subject->getNumItems());
    }

    public function testGetContentName()
    {
        $productName = 'Test Product';
        $this->fbeHelperMock->expects($this->once())
            ->method('getObject')
            ->with(CheckoutSession::class)
            ->willReturn($this->checkoutSessionMock);
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->once())
            ->method('getName')
            ->willReturn($productName);
        $this->assertEquals([$productName], $this->subject->getContentName());
    }

    public function testGetLastOrderRealOrderEntityId()
    {
        $orderId = 1;
        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($orderId);
        $this->assertEquals($orderId, $this->subject->getLastOrderRealOrderEntityId());
    }

    public function testGetEventId()
    {
        $eventId = 'ds232-afaef232-afasf232';
        $this->capiEventIdHandlerMock->expects($this->once())
            ->method('getMetaEventId')
            ->with('facebook_businessextension_ssapi_purchase')
            ->willReturn($eventId);
        $this->assertEquals($eventId, $this->subject->getEventId());
    }
}
