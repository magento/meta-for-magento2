<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver as CatalogLayerResolver;
use Meta\Conversion\Block\Pixel\ViewCategory;
use Magento\Catalog\Model\Category;

class ViewCategoryTest extends TestCase
{
    private $contextMock;
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $systemConfigMock;
    private $escaperMock;
    private $checkoutSessionMock;
    private $catalogLayerResolverMock;
    private $catalogLayerMock;

    private $viewCategory;

    protected function setUp(): void
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

        $this->systemConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogLayerMock = $this->getMockBuilder(Layer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogLayerResolverMock = $this->getMockBuilder(CatalogLayerResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogLayerResolverMock->method('get')->willReturn($this->catalogLayerMock);

        $this->viewCategory = new ViewCategory(
            $this->contextMock,
            $this->fbeHelperMock,
            $this->magentoDataHelperMock,
            $this->systemConfigMock,
            $this->escaperMock,
            $this->checkoutSessionMock,
            $this->catalogLayerResolverMock
        );
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_view_category', $this->viewCategory->getEventToObserveName());
    }

    public function testGetCategoryName()
    {
        $categoryName = 'Test Category';
        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogLayerMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn($categoryMock);
        $categoryMock->expects($this->once())
            ->method('getName')
            ->willReturn($categoryName);

        $this->assertEquals($categoryName, $this->viewCategory->getCategoryName());
    }

    public function testGetCategoryId()
    {
        $categoryId = 1;
        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogLayerMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn($categoryMock);
        $categoryMock->expects($this->once())
            ->method('getId')
            ->willReturn($categoryId);
        $this->assertEquals($categoryId, $this->viewCategory->getCategoryId());
    }

}
