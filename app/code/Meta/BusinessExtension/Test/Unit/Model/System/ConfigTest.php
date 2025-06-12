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
        $this->resourceConfig = $this->getMockBuilder(ResourceConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cacheInterface = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $composerInformation = $this->getMockBuilder(ComposerInformation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->facebookInstalledFeature = $this->getMockBuilder(FacebookInstalledFeature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configObj = new Config(
            $this->storeManager,
            $this->scopeConfig,
            $this->resourceConfig,
            $cacheInterface,
            $composerInformation,
            $this->facebookInstalledFeature
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

    /**
     * Test getCommerceExtensionBaseURL function
     * 
     * @return void
     */
    public function testGetCommerceExtensionBaseURL(): void
    {
        $expectedData = 'https://www.facebook.com/commerce/';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getCommerceExtensionBaseURL();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getStoreManager function
     * 
     * @return void
     */
    public function testGetStoreManager(): void
    {
        $actualData = $this->configObj->getStoreManager();
        $this->assertInstanceOf(StoreManagerInterface::class, $actualData);
    }

    /**
     * Test getDefaultStoreId function
     * 
     * @return void
     */
    public function testGetDefaultStoreId(): void
    {
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getDefaultStoreView')->willReturn($storeMock);

        $actualData = $this->configObj->getDefaultStoreId();
        $this->assertEquals(1, $actualData);
    }

    /**
     * Test getDefaultStoreId function
     * 
     * @return void
     */
    public function testGetDefaultStoreIdWithNull(): void
    {
        $this->storeManager->method('getDefaultStoreView')->willReturn(null);

        $actualData = $this->configObj->getDefaultStoreId();
        $this->assertEquals(null, $actualData);
    }

    /**
     * Test getOutOfStockThreshold function
     * 
     * @return void
     */
    public function testGetOutOfStockThreshold(): void
    {
        $expectedData = 10;

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getOutOfStockThreshold();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getOutOfStockThreshold function
     * 
     * @return void
     */
    public function testIsOrderSyncEnabled(): void
    {
        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->isOrderSyncEnabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test getDefaultOrderStatus function
     * 
     * @return void
     */
    public function testGetDefaultOrderStatus(): void
    {
        $expectedData = 'Pending';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getDefaultOrderStatus();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test shouldUseDefaultFulfillmentAddress function
     * 
     * @return void
     */
    public function testShouldUseDefaultFulfillmentAddress(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->shouldUseDefaultFulfillmentAddress();
        $this->assertTrue($actualData);
    }

    /**
     * Test isAutoNewsletterSubscriptionOn function
     * 
     * @return void
     */
    public function testIsAutoNewsletterSubscriptionOn(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->isAutoNewsletterSubscriptionOn();
        $this->assertTrue($actualData);
    }

    /**
     * Test saveConfig function
     * 
     * @return void
     */
    public function testSaveConfig(): void
    {
        $this->resourceConfig->expects($this->once())
            ->method('saveConfig')
            ->willReturnSelf();

        $actualData = $this->configObj->saveConfig('facebook/promotions/enable_promotions_sync', '1', ScopeInterface::SCOPE_STORE);
        $this->assertInstanceOf(Config::class, $actualData);
    }

    /**
     * Test saveConfig function
     * 
     * @return void
     */
    public function testSaveConfigWithoutStoreId(): void
    {
        $this->resourceConfig->expects($this->once())
            ->method('saveConfig')
            ->willReturnSelf();

        $actualData = $this->configObj->saveConfig('facebook/promotions/enable_promotions_sync', '1');
        $this->assertInstanceOf(Config::class, $actualData);
    }

    /**
     * Test deleteConfig function
     * 
     * @return void
     */
    public function testDeleteConfig(): void
    {
        $this->resourceConfig->expects($this->once())
            ->method('deleteConfig')
            ->willReturnSelf();

        $actualData = $this->configObj->deleteConfig('facebook/promotions/enable_promotions_sync', '1', ScopeInterface::SCOPE_STORE);
        $this->assertInstanceOf(Config::class, $actualData);
    }

    /**
     * Test deleteConfig function
     * 
     * @return void
     */
    public function testDeleteConfigWithoutStoreId(): void
    {
        $this->resourceConfig->expects($this->once())
            ->method('deleteConfig')
            ->willReturnSelf();

        $actualData = $this->configObj->deleteConfig('facebook/promotions/enable_promotions_sync', '1');
        $this->assertInstanceOf(Config::class, $actualData);
    }

    /**
     * Test getAccessToken function
     * 
     * @return void
     */
    public function testGetAccessToken(): void
    {
        $expectedData = '3ert^&ui88';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getAccessToken();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getClientAccessToken function
     * 
     * @return void
     */
    public function testGetClientAccessToken(): void
    {
        $expectedData = '3ert^&ui88';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getClientAccessToken();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getExternalBusinessId function
     * 
     * @return void
     */
    public function testGetExternalBusinessId(): void
    {
        $expectedData = '3ert^&ui88';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getExternalBusinessId();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getPixelId function
     * 
     * @return void
     */
    public function testGetPixelId(): void
    {
        $expectedData = '3ert^&ui88';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getPixelId();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getPixelAamSettings function
     * 
     * @return void
     */
    public function testGetPixelAamSettings(): void
    {
        $expectedData = '3ert^&ui88';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getPixelAamSettings();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getProfiles function
     * 
     * @return void
     */
    public function testGetProfiles(): void
    {
        $expectedData = '["508541655670521"]';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getProfiles();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getPageId function
     * 
     * @return void
     */
    public function testGetPageId(): void
    {
        $expectedData = '3ert^&ui88';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getPageId();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getGraphAPIVersion function
     * 
     * @return void
     */
    public function testGetGraphAPIVersion(): void
    {
        $expectedData = 'v20';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getGraphAPIVersion();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getCommercePartnerIntegrationId function
     * 
     * @return void
     */
    public function testGetCommercePartnerIntegrationId(): void
    {
        $expectedData = 'ww!!20ert$';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getCommercePartnerIntegrationId();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test isDebugMode function
     * 
     * @return void
     */
    public function testIsDebugMode(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->isDebugMode();
        $this->assertTrue($actualData);
    }

    /**
     * Test getApiVersion function
     * 
     * @return void
     */
    public function testGetApiVersion(): void
    {
        $expectedData = 'v20';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getApiVersion();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getApiVersionLastUpdate function
     * 
     * @return void
     */
    public function testGetApiVersionLastUpdate(): void
    {
        $expectedData = '1.2.226';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getApiVersionLastUpdate();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getShippingMethodsMap function
     * 
     * @return void
     */
    public function testGetShippingMethodsMap(): void
    {
        $expectedData = [
            'standard' => 'standard_method',
            'expedited' => 'expedited_method',
            'rush' => 'rush_method',
        ];
        $returnValues = array_values($expectedData);
        $callCount = 0;
        $this->scopeConfig->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnCallback(function () use (&$callCount, $returnValues) {
                $valueToReturn = $returnValues[$callCount];
                $callCount++;
                return $valueToReturn;
            });

        $actualData = $this->configObj->getShippingMethodsMap();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getShippingMethodsLabelMap function
     * 
     * @return void
     */
    public function testGetShippingMethodsLabelMap(): void
    {
        $expectedData = [
            'standard' => 'Standard Shipping',
            'expedited' => 'Expedited Shipping',
            'rush' => 'Rush Shipping',
        ];
        $returnValues = array_values($expectedData);
        $callCount = 0;
        $this->scopeConfig->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnCallback(function () use (&$callCount, $returnValues) {
                $valueToReturn = $returnValues[$callCount];
                $callCount++;
                return $valueToReturn;
            });

        $actualData = $this->configObj->getShippingMethodsLabelMap();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test isCatalogSyncEnabled function
     * 
     * @return void
     */
    public function testIsCatalogSyncEnabled(): void
    {
        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->isCatalogSyncEnabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isPromotionsSyncEnabled function
     * 
     * @return void
     */
    public function testIsPromotionsSyncEnabled(): void
    {
        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->isPromotionsSyncEnabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test getAllFBEInstalledStores function
     * 
     * @return void
     */
    public function testGetAllFBEInstalledStores(): void
    {
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStores')->willReturn([$storeMock]);

        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->getAllFBEInstalledStores();
        $this->assertIsArray($actualData);
    }

    /**
     * Test getAllOnsiteFBEInstalledStores function
     * 
     * @return void
     */
    public function testGetAllOnsiteFBEInstalledStores(): void
    {
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStores')->willReturn([$storeMock]);

        $this->scopeConfig->expects($this->exactly(4))
            ->method('getValue')
            ->willReturn(true);

        $actualData = $this->configObj->getAllOnsiteFBEInstalledStores();
        $this->assertIsArray($actualData);
    }

    /**
     * Test getFeedId function
     * 
     * @return void
     */
    public function testGetFeedId(): void
    {
        $expectedData = 'ffgg_off';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getFeedId();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getOffersFeedId function
     * 
     * @return void
     */
    public function testGetOffersFeedId(): void
    {
        $expectedData = 'ffgg_off';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getOffersFeedId();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getProductIdentifierAttr function
     * 
     * @return void
     */
    public function testGetProductIdentifierAttr(): void
    {
        $expectedData = 'sku';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getProductIdentifierAttr();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test isAllCategoriesSyncEnabled function
     * 
     * @return void
     */
    public function testIsAllCategoriesSyncEnabled(): void
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isAllCategoriesSyncEnabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isPriceInclTax function
     * 
     * @return void
     */
    public function testIsPriceInclTax(): void
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isPriceInclTax();
        $this->assertTrue($actualData);
    }

    /**
     * Test isServerTestModeEnabled function
     * 
     * @return void
     */
    public function testIsServerTestModeEnabled(): void
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isServerTestModeEnabled();
        $this->assertTrue($actualData);
    }
    
    /**
     * Test getServerTestCode function
     * 
     * @return void
     */
    public function testGetServerTestCode(): void
    {
        $expectedData = 'test_124';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getServerTestCode();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getWeightUnit function
     * 
     * @return void
     */
    public function testGetWeightUnit(): void
    {
        $expectedData = 'lbs';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getWeightUnit();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test isAdditionalAttributesSyncDisabled function
     * 
     * @return void
     */
    public function testIsAdditionalAttributesSyncDisabled(): void
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isAdditionalAttributesSyncDisabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isUnsupportedProductsDisabled function
     * 
     * @return void
     */
    public function testIsUnsupportedProductsDisabled(): void
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isUnsupportedProductsDisabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test getProductsFetchBatchSize function
     * 
     * @return void
     */
    public function testGetProductsFetchBatchSize(): void
    {
        $expectedData = 200;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getProductsFetchBatchSize();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * Test getProductsFetchBatchSize function
     * 
     * @return void
     */
    public function testGetProductsFetchBatchSizeWithDefault(): void
    {
        $expectedData = 0;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->getProductsFetchBatchSize();
        $this->assertSame(200, $actualData);
    }

    /**
     * Test isMemoryProfilingEnabled function
     * 
     * @return void
     */
    public function testIsMemoryProfilingEnabled(): void
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isMemoryProfilingEnabled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isFBECatalogInstalled function
     * 
     * @return void
     */
    public function testIsFBECatalogInstalledWithFalse(): void
    {
        $expectedData = false;
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStores')->willReturn([$storeMock]);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isFBECatalogInstalled();
        $this->assertFalse($actualData);
    }

    /**
     * Test isFBECatalogInstalled function
     * 
     * @return void
     */
    public function testIsFBECatalogInstalledWithTrue(): void
    {
        $expectedData = true;
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStores')->willReturn([$storeMock]);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $this->facebookInstalledFeature->expects($this->once())
            ->method('doesFeatureTypeExist')
            ->with('catalog', 1)
            ->willReturn(true);
        $actualData = $this->configObj->isFBECatalogInstalled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isFBEPixelInstalled function
     * 
     * @return void
     */
    public function testIsFBEPixelInstalledWithTrue(): void
    {
        $expectedData = true;
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStores')->willReturn([$storeMock]);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $this->facebookInstalledFeature->expects($this->once())
            ->method('doesFeatureTypeExist')
            ->with('pixel', 1)
            ->willReturn(true);
        $actualData = $this->configObj->isFBEPixelInstalled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isFBEAdsInstalled function
     * 
     * @return void
     */
    public function testIsFBEAdsInstalledWithTrue(): void
    {
        $expectedData = true;
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManager->method('getStores')->willReturn([$storeMock]);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($expectedData);

        $this->facebookInstalledFeature->expects($this->once())
            ->method('doesFeatureTypeExist')
            ->with('ads', 1)
            ->willReturn(true);
        $actualData = $this->configObj->isFBEAdsInstalled();
        $this->assertTrue($actualData);
    }

    /**
     * Test isFBEShopInstalled function
     * 
     * @return void
     */
    public function testIsFBEShopInstalled(): void
    {
        $expectedData = false;

        $this->scopeConfig->expects($this->exactly(3))
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isFBEShopInstalled();
        $this->assertFalse($actualData);
    }

    /**
     * Test isInstalledShopOnsiteEligible function
     * 
     * @return void
     */
    public function testIsInstalledShopOnsiteEligible(): void
    {
        $expectedData = false;

        $this->scopeConfig->expects($this->exactly(1))
            ->method('getValue')
            ->willReturn($expectedData);

        $actualData = $this->configObj->isInstalledShopOnsiteEligible();
        $this->assertFalse($actualData);
    }

    /**
     * Test castStoreIdAsInt function
     * 
     * @return void
     */
    public function testCastStoreIdAsInt(): void
    {
        $storeIdString = 'string';

        $actualData = $this->configObj->castStoreIdAsInt($storeIdString);
        $this->assertSame(null, $actualData);
    }

    /**
     * Test castStoreIdAsInt function
     * 
     * @return void
     */
    public function testCastStoreIdAsIntWithInt(): void
    {
        $storeIdString = 1234;

        $actualData = $this->configObj->castStoreIdAsInt($storeIdString);
        $this->assertSame($storeIdString, $actualData);
    }
}
