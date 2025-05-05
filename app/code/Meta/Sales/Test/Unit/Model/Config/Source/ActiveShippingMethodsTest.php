<?php
declare(strict_types=1);

namespace Meta\Sales\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Sales\Model\Config\Source\ActiveShippingMethods;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ActiveShippingMethodsTest extends TestCase
{
    private $scopeConfigMock;
    private $shippingConfigMock;
    private $object;
    private $subject;
    
    public function setUp(): void
    {

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingConfigMock = $this->getMockBuilder(ShippingConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object = new ObjectManager($this);
        $this->subject = $this->object->getObject(ActiveShippingMethods::class, [
            'scopeConfig' => $this->scopeConfigMock,
            'shippingConfig' => $this->shippingConfigMock
        ]);
    }

    public function testToOptionArray()
    {
        $carrierMock = $this->getMockBuilder(AbstractCarrierInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shippingConfigMock->expects($this->once())
            ->method('getAllCarriers')
            ->willReturn([$carrierMock]);

        $this->subject->toOptionArray();
    }
}
