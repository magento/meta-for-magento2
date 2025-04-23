<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\CommerceExtensionHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CommerceExtensionHelperTest extends TestCase
{
    /**
     * @var GraphAPIAdapter
     */
    private $graphAPIAdapter;

    /**
     * @var SystemConfig
     */
    private $systemConfig;
    
    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->graphAPIAdapter = $this->createMock(GraphAPIAdapter::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);

        $objectManager = new ObjectManager($this);
        $this->commerceExtensionHelperMockObj = $objectManager->getObject(
            CommerceExtensionHelper::class,
            [
                'systemConfig' => $this->systemConfig,
                'graphAPIAdapter' => $this->graphAPIAdapter
            ]
        );
    }

    /**
     * Test getSplashPageURL function
     * 
     * @return void
     */
    public function testGetSplashPageURL(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isActiveExtension')
            ->willReturn(false);

        $expectedValue = 'https://business.facebook.com/fbe-iframe-get-started/?';

        $this->assertEquals($expectedValue, $this->commerceExtensionHelperMockObj->getSplashPageURL());
    }

    /**
     * Test getSplashPageURL function
     * 
     * @return void
     */
    public function testGetSplashPageURLWithIsActiveExtensionTrue(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isActiveExtension')
            ->willReturn(true);

        $this->systemConfig->expects($this->once())
            ->method('getCommerceExtensionBaseURL')
            ->willReturn('https://business.facebook.com/');

        $expectedValue = 'https://business.facebook.com/commerce_extension/splash/?';

        $this->assertEquals($expectedValue, $this->commerceExtensionHelperMockObj->getSplashPageURL());
    }

    /**
     * Test getSplashPageURL function
     * 
     * @return void
     */
    public function testGetPopupOrigin(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isActiveExtension')
            ->willReturn(true);

        $this->systemConfig->expects($this->once())
            ->method('getCommerceExtensionBaseURL')
            ->willReturn('https://business.facebook.com/');

        $expectedValue = 'https://business.facebook.com/';

        $this->assertEquals($expectedValue, $this->commerceExtensionHelperMockObj->getPopupOrigin());
    }

    /**
     * Test getPopupOrigin function
     * 
     * @return void
     */
    public function testGetPopupOriginWithIsActiveExtensionFalse(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isActiveExtension')
            ->willReturn(false);

        $expectedValue = 'https://business.facebook.com';

        $this->assertEquals($expectedValue, $this->commerceExtensionHelperMockObj->getPopupOrigin());
    }

    /**
     * Test isCommerceExtensionEnabled function
     * 
     * @return void
     */
    public function testIsCommerceExtensionEnabled(): void
    {
        $storeId = 99;
        $this->systemConfig->expects($this->once())
            ->method('getCommercePartnerIntegrationId')
            ->with($storeId)
            ->willReturn('');

        $this->systemConfig->expects($this->once())
            ->method('isActiveExtension')
            ->willReturn(true);

        $this->assertTrue($this->commerceExtensionHelperMockObj->isCommerceExtensionEnabled($storeId));
    }

    /**
     * Test isCommerceExtensionEnabled function
     * 
     * @return void
     */
    public function testIsCommerceExtensionEnabledReturnFalse(): void
    {
        $storeId = 99;
        $this->systemConfig->expects($this->once())
            ->method('getCommercePartnerIntegrationId')
            ->with($storeId)
            ->willReturn('');

        $this->systemConfig->expects($this->once())
            ->method('isActiveExtension')
            ->willReturn(false);

        $this->assertFalse($this->commerceExtensionHelperMockObj->isCommerceExtensionEnabled($storeId));
    }

    /**
     * Test hasCommerceExtensionPermissionError function
     * 
     * @return void
     */
    public function testHasCommerceExtensionPermissionError(): void
    {
        $storeId = 99;
        $businessId = 999;
        $accessToken = 'qw!3e#$^';
        $url = 'https://business.facebook.com';

        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->with($storeId)
            ->willReturn($businessId);

        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->with($storeId)
            ->willReturn($accessToken);

        $this->graphAPIAdapter->expects($this->once())
            ->method('getCommerceExtensionIFrameURL')
            ->with($businessId,$accessToken)
            ->willReturn($url);

        $this->assertFalse($this->commerceExtensionHelperMockObj->hasCommerceExtensionPermissionError($storeId));
    }
}