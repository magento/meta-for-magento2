<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\ViewModel\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\ViewModel\Pixel\Common;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class CommonTest extends TestCase
{
    private $systemConfigMock;
    private $subject;

    public function setUp(): void
    {
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Common::class, ['systemConfig' => $this->systemConfigMock]);
    }

    public function testGetFacebookPixelID()
    {
        $pixelId = '12345-234-sdfgt-234';
        $this->systemConfigMock->expects($this->once())
            ->method('getPixelId')
            ->willReturn($pixelId);
        $this->assertEquals($pixelId, $this->subject->getFacebookPixelID());
    }
}
