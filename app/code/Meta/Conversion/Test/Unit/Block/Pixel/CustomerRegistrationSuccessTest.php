<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Block\Pixel\CustomerRegistrationSuccess;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

class CustomerRegistrationSuccessTest extends TestCase
{
    private $contextMock;
    private $fbeHelperMock;
    private $magentoDataHelperMock;
    private $systemConfigMock;
    private $escaperMock;
    private $checkoutSessionMock;
    private $customerSessionMock;
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
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->addMethods(['getMetaEventIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(CustomerRegistrationSuccess::class, [
            'context' => $this->contextMock,
            'fbeHelper' => $this->fbeHelperMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'escaper' => $this->escaperMock,
            'checkoutSession' => $this->checkoutSessionMock,
            'customerSession' => $this->customerSessionMock
        ]);
    }

    public function testGetContentType()
    {
        $this->assertEquals('customer_registration', $this->subject->getContentType());
    }

    public function testGetEventToObserveName()
    {
        $this->assertEquals('facebook_businessextension_ssapi_customer_registration_success', $this->subject->getEventToObserveName());
    }

    public function testGetEventId()
    {
        $eventId = '34567fg-dafsd5-adfsg';
        $metaEventIds = [
            'facebook_businessextension_ssapi_customer_registration_success' => $eventId
        ];

        $this->customerSessionMock->expects($this->once())
            ->method('getMetaEventIds')
            ->willReturn($metaEventIds);

        $this->assertEquals($eventId, $this->subject->getEventId());
    }

    public function testGetEventIdNull()
    {
        $eventId = '34567fg-dafsd5-adfsg';
        $metaEventIds = [
            'facebook_businessextension_ssapi_customer_registration' => $eventId
        ];

        $this->customerSessionMock->expects($this->once())
            ->method('getMetaEventIds')
            ->willReturn($metaEventIds);

        $this->assertNull($this->subject->getEventId());
    }
}
