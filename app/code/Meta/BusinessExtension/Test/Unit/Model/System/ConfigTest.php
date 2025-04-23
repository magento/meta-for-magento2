<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\System;

use PHPUnit\Framework\TestCase;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Composer\ComposerInformation;
use Meta\BusinessExtension\Model\System\Config;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Magento\Store\Model\Store as CoreStore;


class ConfigTest extends TestCase
{
    private const APP_ID = '195311308289826';
    private const COMMERCE_ACCOUNT_ID = '12121212';
    private const CATALOG_ID = '25';
    private const SUPPORT_URL_ID = '99';
    private const PROMOTION_URL_ID = '100';
    private const IS_ACTIVE = true;
    private const ADDRESS = '143 ET 57 ST';

    /**
     * @var Config
     */
    private $configObj;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceConfig = $this->getMockBuilder(ResourceConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cacheInterface = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $composerInformation = $this->getMockBuilder(ComposerInformation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $facebookInstalledFeature = $this->getMockBuilder(FacebookInstalledFeature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configObj = new Config(
            $this->storeManager,
            $this->scopeConfig,
            $resourceConfig,
            $cacheInterface,
            $composerInformation,
            $facebookInstalledFeature
        );

        $this->setDbReturnData();
    }

    /**
     * Test getAppId
     * 
     * @return void
     */
    public function testGetAppId(): void
    {
        $this->assertEquals(self::APP_ID, $this->configObj->getAppId());
    }

    /**
     * Test getModuleVersion
     * 
     * @return void
     */
    public function testGetModuleVersion(): void
    {
        $expectedVersion = '1.4.4-dev';
        $reflection = new \ReflectionClass(Config::class);
        $configModulesProperty = $reflection->getProperty('version');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->configObj, $expectedVersion);

        $actualVersion = $this->configObj->getModuleVersion();
        $this->assertSame($expectedVersion, $actualVersion);
    }

    /**
     * Test getCommerceManagerUrl
     * 
     * @return void
     */
    public function testGetCommerceManagerUrl(): void
    {
        $expectedData = 'https://www.facebook.com/commerce/' . self::COMMERCE_ACCOUNT_ID;

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(self::COMMERCE_ACCOUNT_ID);

        $actualData = $this->configObj->getCommerceManagerUrl();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getCatalogManagerUrl
     * 
     * @return void
     */
    public function testGetCatalogManagerUrl(): void
    {
        $expectedData = 'https://www.facebook.com/products/catalogs/' . self::CATALOG_ID . '/products';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(self::CATALOG_ID);

        $actualData = $this->configObj->getCatalogManagerUrl();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getSupportUrl
     * 
     * @return void
     */
    public function testGetSupportUrl(): void
    {
        $expectedData = 'https://www.facebook.com/commerce/' . self::SUPPORT_URL_ID . '/support/';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(self::SUPPORT_URL_ID);

        $actualData = $this->configObj->getSupportUrl();
        $this->assertSame($expectedData, $actualData);
    }

     /**
     * Test getPromotionsUrl
     * 
     * @return void
     */
    public function testGetPromotionsUrl(): void
    {
        $expectedData = 'https://www.facebook.com/commerce/' . self::PROMOTION_URL_ID . '/promotions/discounts/';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(self::PROMOTION_URL_ID);

        $actualData = $this->configObj->getPromotionsUrl();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test isActiveExtension
     * 
     * @return void
     */
    public function testIsActiveExtension(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(self::IS_ACTIVE);

        $this->assertTrue($this->configObj->isActiveExtension());
    }

    /**
     * Test isFBEInstalled
     * 
     * @return void
     */
    public function testIsFBEInstalled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(self::IS_ACTIVE);

        $this->assertTrue($this->configObj->isFBEInstalled());
    }

    /**
     * Test getFulfillmentAddress
     * 
     * @return void
     */
    public function testGetFulfillmentAddress(): void
    {
        $this->scopeConfig->expects($this->exactly(6))
            ->method('getValue')
            ->willReturn(self::ADDRESS);

        $actualData = $this->configObj->getFulfillmentAddress();

        $this->assertIsArray($actualData);
        $this->assertContains(self::ADDRESS, $actualData);
    }

    /**
     * Set the default value
     * 
     * @return void
     */
    public function setDbReturnData(): void
    {
        $this->storeManager->method('isSingleStoreMode')
            ->willReturn(false);
        
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStore')->willReturn($storeMock);
    }
}