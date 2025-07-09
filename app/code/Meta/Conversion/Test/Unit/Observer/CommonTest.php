<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Observer\Common;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Cookie\Helper\Cookie as CookieHelper;

class CommonTest extends TestCase
{
    private $jsonHelperMock;
    private $cookieMetadataFactoryMock;
    private $cookieMetadataMock;
    private $publicCookieMetadataMock;
    private $cookieManagerMock;
    private $cookieHelperMock;
    private $subject;
    private $cookieName = 'random-cookie';
    private $cookieData = [
        'content_name' => 'John Doe',
        'value' => 1,
        'status' => "True"
    ];

    protected function setUp(): void
    {
        $this->jsonHelperMock = $this->getMockBuilder(JsonHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieMetadataMock = $this->getMockBuilder(CookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cookieHelperMock = $this->getMockBuilder(CookieHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Common::class, [
            'cookieManager' => $this->cookieManagerMock,
            'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
            'jsonHelper' => $this->jsonHelperMock,
            'cookieHelper' => $this->cookieHelperMock,
        ]);
    }

    public function testSetCookieForMetaPixelCookieRestriction()
    {
        $this->cookieHelperMock->expects($this->once())
            ->method('isCookieRestrictionModeEnabled')
            ->willReturn(true);
        $this->cookieHelperMock->expects($this->once())
            ->method('isUserNotAllowSaveCookie')
            ->willReturn(true);

        $this->assertNull($this->subject->setCookieForMetaPixel($this->cookieName, $this->cookieData));
    }

    public function testSetCookieForMetaPixel()
    {
        $this->cookieHelperMock->expects($this->once())
            ->method('isCookieRestrictionModeEnabled')
            ->willReturn(true);
        $this->cookieHelperMock->expects($this->once())
            ->method('isUserNotAllowSaveCookie')
            ->willReturn(false);
        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);
        $this->publicCookieMetadataMock->expects($this->once())
            ->method('setDuration')
            ->with(3600)
            ->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())
            ->method('setPath')
            ->with('/')
            ->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())
            ->method('setHttpOnly')
            ->with(false)
            ->willReturnSelf();
        $this->cookieManagerMock->expects($this->once())
            ->method('setPublicCookie')
//            ->with($this->cookieName, json_encode($this->cookieData), $this->publicCookieMetadataMock);
            ->with($this->cookieName, null, $this->publicCookieMetadataMock);
        $this->subject->setCookieForMetaPixel($this->cookieName, $this->cookieData);
    }
}
