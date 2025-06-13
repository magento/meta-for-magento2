<?php
declare(strict_types=1);

namespace Meta\Promotions\Test\Unit\Block\Adminhtml\System\Config;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Promotions\Block\Adminhtml\System\Config\PushPromotions;
use Magento\Backend\Block\Template\Context;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\RequestInterface;

class PushPromotionsTest extends TestCase
{
    private $contextMock;
    private $systemConfigMock;
    private $requestMock;
    private $object;
    private $subject;

    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->onlyMethods(['getCommerceAccountId', 'getPromotionsUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->object = new ObjectManager($this);
        $this->subject = $this->object->getObject(PushPromotions::class, [
            'context' => $this->contextMock,
            'systemConfig' => $this->systemConfigMock,
            'data' => []
        ]);
    }

    public function testGetCommerceAccountId()
    {
        $storeId = 1;
        $commerceAccountId = '3456765456';

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($storeId);
        $this->systemConfigMock->expects($this->once())
            ->method('getCommerceAccountId')
            ->with($storeId)
            ->willReturn($commerceAccountId);

        $this->subject->getCommerceAccountId();
    }

    public function testGetCommerceManagerPromotionsUrl()
    {
        $storeId = 1;
        $promotionUrl = 'https://www.facebook.com/commerce/'.$storeId.'/promotions/discounts/';

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($storeId);
        $this->systemConfigMock->expects($this->once())
            ->method('getPromotionsUrl')
            ->with($storeId)
            ->willReturn($promotionUrl);

        $this->subject->getCommerceManagerPromotionsUrl();
    }
}
