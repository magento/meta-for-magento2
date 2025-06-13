<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\InitiateCheckout;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Catalog\Model\Product;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Meta\Conversion\Model\CapiEventIdHandler;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Escaper;

class InitiateCheckoutTest extends TestCase
{
    private $contextMock;
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $systemConfigMock;
    private $escaperMock;
    private $checkoutSessionMock;
    private $quoteMock;
    private $quoteItemMock;
    private $productMock;
    private $pricingHelperMock;
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
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pricingHelperMock = $this->getMockBuilder(PricingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiEventIdHandlerMock = $this->getMockBuilder(CapiEventIdHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(InitiateCheckout::class, [
            'context' => $this->contextMock,
            'fbeHelper' => $this->fbeHelperMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'escaper' => $this->escaperMock,
            'checkoutSession' => $this->checkoutSessionMock,
            'pricingHelper' => $this->pricingHelperMock,
            'capiEventIdHandler' => $this->capiEventIdHandlerMock,
        ]);
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_initiate_checkout', $this->subject->getEventToObserveName());
    }

    public function testGetContentTypeQuote()
    {
        $this->assertEquals('product', $this->subject->getContentTypeQuote());
    }

    public function testGetEventId()
    {
        $eventId = 'sdfg-234e2-sfgd23-123ss';
        $this->capiEventIdHandlerMock->expects($this->once())
            ->method('getMetaEventId')
            ->with('facebook_businessextension_ssapi_initiate_checkout')
            ->willReturn($eventId);
        $this->assertEquals($eventId, $this->subject->getEventId());
    }

    public function testGetContentIDs()
    {
        $productId = 1;
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getContentId')
            ->with($this->productMock)
            ->willReturn($productId);
        $this->assertEquals([$productId], $this->subject->getContentIDs());
    }

    public function testGetValue()
    {
        $itemPrice = 10.00;
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCartTotal')
            ->with($this->quoteMock)
            ->willReturn($itemPrice);
        $this->assertEquals($itemPrice, $this->subject->getValue());
    }

    public function testGetContents()
    {
        $productId = 1;
        $productQty = 1;
        $productPrice = 10.00;
        $contents = [
            [
                'id' => $productId,
                'quantity' => $productQty,
                'item_price' => $productPrice
            ]
        ];
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn($productPrice);
        $this->pricingHelperMock->expects($this->once())
            ->method('currency')
            ->with($productPrice, false, false)
            ->willReturn($productPrice);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getContentId')
            ->with($this->productMock)
            ->willReturn($productId);
        $this->quoteItemMock->expects($this->once())
            ->method('getQty')
            ->willReturn($productQty);
        $this->assertEquals($contents, $this->subject->getContents());
    }

    public function testGetNumItems()
    {
        $itemQty = 1;
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCartNumItems')
            ->with($this->quoteMock)
            ->willReturn($itemQty);
        $this->assertEquals($itemQty, $this->subject->getNumItems());
    }

    public function testGetContentCategory()
    {
        $categoryName = 'Test Category';
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCategoriesForProduct')
            ->with($this->productMock)
            ->willReturn($categoryName);
        $this->assertEquals($categoryName, $this->subject->getContentCategory());
    }
}
