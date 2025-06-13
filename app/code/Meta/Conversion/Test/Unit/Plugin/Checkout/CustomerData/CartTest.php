<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Plugin\Checkout\CustomerData;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Plugin\Checkout\CustomerData\Cart;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Checkout\CustomerData\Cart as MagentoCart;

class CartTest extends TestCase
{
    private $magentoDataHelperMock;
    private $checkoutSessionMock;
    private $quoteMock;
    private $subject;

    public function setUp(): void
    {
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Cart::class, [
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'checkoutSession' => $this->checkoutSessionMock
        ]);
    }

    public function testGetQuote()
    {
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->subject->getQuote();
    }

    public function testAfterGetSectionData()
    {
        $cartPayload = [
            'content_category' => 'test,test1',
            'content_ids'      => [1,2,3],
            'contents'         => ['test','test1'],
            'currency'         => 'USD',
            'value'            => 12.50
        ];
        $magentoCartMock = $this->getMockBuilder(MagentoCart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCartPayload')
            ->with($this->quoteMock)
            ->willReturn($cartPayload);

        $this->assertEquals(['meta_payload' => $cartPayload], $this->subject->afterGetSectionData($magentoCartMock, []));
    }
}
