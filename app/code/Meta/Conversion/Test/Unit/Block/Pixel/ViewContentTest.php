<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\ViewContent;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class ViewContentTest extends TestCase
{
    private $contextMock;
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $systemConfigMock;
    private $escaperMock;
    private $checkoutSessionMock;
    private $catalogHelperMock;
    private $layoutMock;
    private $blockMock;
    private $productMock;
    private $priceHelperMock;
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
        $this->catalogHelperMock = $this->getMockBuilder(CatalogHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->blockMock = $this->getMockBuilder(BlockInterface::class)
            ->addMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceHelperMock = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(ViewContent::class, [
            'context' => $this->contextMock,
            'fbeHelper' => $this->fbeHelperMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'escaper' => $this->escaperMock,
            'checkoutSession' => $this->checkoutSessionMock,
            'catalogHelper' => $this->catalogHelperMock,
        ]);
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_view_content', $this->subject->getEventToObserveName());
    }

    public function testGetCurrentProduct()
    {
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('product.info')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->assertEquals($this->productMock, $this->subject->getCurrentProduct());
    }

    public function testGetCurrentProductException()
    {
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('product.info')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())
            ->method('getProduct')
            ->willThrowException(new \Exception('Product not found'));
        $this->assertNull($this->subject->getCurrentProduct());
    }

    public function testGetProductIdNull()
    {
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('product.info')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);
        $this->assertNull($this->subject->getProductId());
    }

    public function testGetProductId()
    {
        $productId = 1;
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('product.info')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn($productId);
        $this->assertEquals($productId, $this->subject->getProductId());
    }

    public function testGetValueNull()
    {
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('product.info')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->assertNull($this->subject->getValue());
    }

    public function testGetValue()
    {
        $productId = 1;
        $productPrice = 10.00;
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('product.info')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn($productId);
        $this->productMock->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn($productPrice);
        $this->fbeHelperMock->expects($this->once())
            ->method('getObject')
            ->with(PriceHelper::class)
            ->willReturn($this->priceHelperMock);
        $this->priceHelperMock->expects($this->once())
            ->method('currency')
            ->with($productPrice, false, false)
            ->willReturn($productPrice);

        $this->assertEquals($productPrice, $this->subject->getValue());
    }
}
