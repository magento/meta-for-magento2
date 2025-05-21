<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\Search;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;

class SearchTest extends TestCase
{
    private $contextMock;
    private $requestMock;
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $systemConfigMock;
    private $escaperMock;
    private $checkoutSessionMock;
    private $subject;

    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

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

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Search::class, [
            'context' => $this->contextMock,
            'fbeHelper' => $this->fbeHelperMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'escaper' => $this->escaperMock,
            'checkoutSession' => $this->checkoutSessionMock
        ]);
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_search', $this->subject->getEventToObserveName());
    }

    public function testGetSearchQuery()
    {
        $searchQuery = 'test';
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('q')
            ->willReturn($searchQuery);
        $this->escaperMock->expects($this->once())
            ->method('escapeHtml')
            ->with($searchQuery)
            ->willReturn($searchQuery);

        $this->assertEquals($searchQuery, $this->subject->getSearchQuery());
    }
}
