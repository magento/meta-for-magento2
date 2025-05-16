<?php

namespace Meta\BusinessExtension\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;
use Meta\BusinessExtension\Block\Adminhtml\Setup;
use Meta\BusinessExtension\Helper\CommerceExtensionHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store as CoreStore;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class SetupTest extends TestCase
{
    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fbeHelper = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->systemConfig = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeRepo = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->apiKeyService = $this->getMockBuilder(ApiKeyService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commerceExtensionHelper = $this->getMockBuilder(CommerceExtensionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adobeCloudConfigInterface = $this->getMockBuilder(AdobeCloudConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestInterface);

        $objectManager = new ObjectManager($this);
        $this->setupMockObj = $objectManager->getObject(
            Setup::class,
            [
                'context' => $context,
                'requestInterface' => $this->requestInterface,
                'fbeHelper' => $this->fbeHelper,
                'systemConfig' => $this->systemConfig,
                'storeRepo' => $this->storeRepo,
                'storeManager' => $this->storeManager,
                'commerceExtensionHelper' => $this->commerceExtensionHelper,
                'apiKeyService' => $this->apiKeyService,
                'adobeCloudConfigInterface' => $this->adobeCloudConfigInterface,
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * Test getApiKey function
     * 
     * @return void
     */
    public function testGetApiKey(): void
    {
        $apiKey = 'sample-api-key';
        $this->apiKeyService->method('upsertApiKey')
            ->willReturn($apiKey);
        $this->assertEquals($apiKey, $this->setupMockObj->upsertApiKey());
    }

    /**
     * Test getSelectedStoreId function
     * 
     * @return void
     */
    public function testGetSelectedStoreIdWithOnlyAdminStore(): void
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Website 1');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $this->assertEquals(null, $this->setupMockObj->getSelectedStoreId());
    }

    /**
     * Test getSelectedStoreId function
     * 
     * @return void
     */
    public function testGetSelectedStoreIdWithFrontendStore(): void
    {
        $storeId = 123;
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 3');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('en_us');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('store_id')
            ->willReturn((string) $storeId);

        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);

        $this->assertEquals($storeId, $this->setupMockObj->getSelectedStoreId());
    }

    /**
     * Test getSelectedStoreId function
     * 
     * @return void
     */
    public function testGetSelectedStoreIdWithFrontendStoreWithNullRequestParam(): void
    {
        $storeId = 123;
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 3');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('en_us');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with(null)
            ->willReturn(null);

         $this->systemConfig->expects($this->once())
            ->method('getDefaultStoreId')
            ->willReturn(1);

        $this->assertEquals(1, $this->setupMockObj->getSelectedStoreId());
    }

    /**
     * Test getSelectedStoreId function
     * 
     * @return void
     */
    public function testGetSelectedStoreIdWithFrontendStoreWillThrowException(): void
    {
        $storeId = 123;
        $exception = new NoSuchEntityException(__('No such entity with storeId = ' . $storeId));
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 3');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('en_us');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('store_id')
            ->willReturn((string) $storeId);

        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);

        $this->systemConfig->expects($this->once())
            ->method('getDefaultStoreId')
            ->willReturn(1);

        $this->storeRepo->expects($this->once())
            ->method('getById')
            ->with($storeId)
            ->willThrowException($exception);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('log')
            ->with("Store with requestStoreId $storeId not found");

        $this->assertEquals(1, $this->setupMockObj->getSelectedStoreId());
    }

    /**
     * Test getPixelAjaxRoute function
     * 
     * @return void
     */
    public function testGetPixelAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbpixel')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbpixel');

        $this->assertEquals('https://facebook.com/fbeadmin/ajax/fbpixel', $this->setupMockObj->getPixelAjaxRoute());
    }

    /**
     * Test getAccessTokenAjaxRoute function
     * 
     * @return void
     */
    public function testGetAccessTokenAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbtoken')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbtoken');

        $this->assertEquals('https://facebook.com/fbeadmin/ajax/fbtoken', $this->setupMockObj->getAccessTokenAjaxRoute());
    }

    /**
     * Test getProfilesAjaxRoute function
     * 
     * @return void
     */
    public function testGetProfilesAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbprofiles')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbprofiles');

        $this->assertEquals('https://facebook.com/fbeadmin/ajax/fbprofiles', $this->setupMockObj->getProfilesAjaxRoute());
    }

    /**
     * Test getAAMSettingsRoute function
     * 
     * @return void
     */
    public function testGetAAMSettingsRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbaamsettings')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbaamsettings');

        $this->assertEquals('https://facebook.com/fbeadmin/ajax/fbaamsettings', $this->setupMockObj->getAAMSettingsRoute());
    }

    /**
     * Test fetchPixelId function
     * 
     * @return void
     */
    public function testFetchPixelId(): void
    {
        $storeId = 123;
        $pixelId = '234';

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('getPixelId')
            ->with($storeId)
            ->willReturn($pixelId);

        $this->assertEquals($pixelId, $this->setupMockObj->fetchPixelId($storeId));
    }

    /**
     * Test isCommerceExtensionEnabled function
     * 
     * @return void
     */
    public function testIsCommerceExtensionEnabled(): void
    {
        $storeId = 123;
        $pixelId = '234';

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('store_id')
            ->willReturn((string) $storeId);

        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('isCommerceExtensionEnabled')
            ->with($storeId)
            ->willReturn(true);

        $this->assertTrue($this->setupMockObj->isCommerceExtensionEnabled());
    }

    /**
     * Test isCommerceExtensionEnabled function
     * 
     * @return void
     */
    public function testIsCommerceExtensionEnabledAssertFalse(): void
    {
        $storeId = 123;
        $pixelId = '234';

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('store_id')
            ->willReturn((string) $storeId);

        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('isCommerceExtensionEnabled')
            ->with($storeId)
            ->willReturn(false);

        $this->assertFalse($this->setupMockObj->isCommerceExtensionEnabled());
    }

    /**
     * Test getPopupOrigin function
     * 
     * @return void
     */
    public function testGetPopupOrigin(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('getPopupOrigin')
            ->willReturn('USA');

        $this->assertSame('USA', $this->setupMockObj->getPopupOrigin());
    }

    /**
     * Test getSplashPageURL function
     * 
     * @return void
     */
    public function testGetSplashPageURL(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('getSplashPageURL')
            ->willReturn('https://facebook.com/splash/page/index');

        $this->assertSame('https://facebook.com/splash/page/index', $this->setupMockObj->getSplashPageURL());
    }

    /**
     * Test getExternalBusinessId function
     * 
     * @return void
     */
    public function testGetExternalBusinessId(): void
    {
        $externalBusinessId = 1234;
        $storeId = 123;

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->willReturn($externalBusinessId);

        $this->assertSame($externalBusinessId, $this->setupMockObj->getExternalBusinessId($storeId));
    }

    /**
     * Test getExternalBusinessId function
     * 
     * @return void
     */
    public function testGetExternalBusinessIdWithStoreIdNullInFunctionParam(): void
    {
        $externalBusinessId = 1234;
        $storeId = 123;

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;

        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('store_id')
            ->willReturn((string) $storeId);

        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);

        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->willReturn(null);

        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('log')
            ->with("Store id---" . $storeId);

        $this->systemConfig->expects($this->once())
            ->method('saveExternalBusinessIdForStore')
            ->willReturnSelf();
        
        $generatedId = $this->setupMockObj->getExternalBusinessId(null);

        $this->assertStringStartsWith('fbe_magento_' . $storeId . '_', $generatedId);
        $this->assertEquals(strlen('fbe_magento_' . $storeId . '_') + 13, strlen($generatedId));
    }

    /**
     * Test persistConfigurationAjaxRoute function
     * 
     * @return void
     */
    public function testPersistConfigurationAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/persistConfiguration')
            ->willReturn('https://facebook.com/fbeadmin/ajax/persistConfiguration');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/persistConfiguration', $this->setupMockObj->persistConfigurationAjaxRoute());
    }

    /**
     * Test fetchPostFBEOnboardingSyncAjaxRoute function
     * 
     * @return void
     */
    public function testFetchPostFBEOnboardingSyncAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/postFBEOnboardingSync')
            ->willReturn('https://facebook.com/fbeadmin/ajax/postFBEOnboardingSync');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/postFBEOnboardingSync', $this->setupMockObj->fetchPostFBEOnboardingSyncAjaxRoute());
    }

    /**
     * Test getCleanCacheAjaxRoute function
     * 
     * @return void
     */
    public function testGetCleanCacheAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/cleanCache')
            ->willReturn('https://facebook.com/fbeadmin/ajax/cleanCache');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/cleanCache', $this->setupMockObj->getCleanCacheAjaxRoute());
    }

    /**
     * Test getReportClientErrorRoute function
     * 
     * @return void
     */
    public function testGetReportClientErrorRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/reportClientError')
            ->willReturn('https://facebook.com/fbeadmin/ajax/reportClientError');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/reportClientError', $this->setupMockObj->getReportClientErrorRoute());
    }

    /**
     * Test getDeleteAssetIdsAjaxRoute function
     * 
     * @return void
     */
    public function testGetDeleteAssetIdsAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbdeleteasset')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbdeleteasset');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/fbdeleteasset', $this->setupMockObj->getDeleteAssetIdsAjaxRoute());
    }

    /**
     * Test getCurrencyCode function
     * 
     * @return void
     */
    public function testGetCurrencyCode(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getStoreCurrencyCode')
            ->willReturn('$');

        $this->assertSame('$', $this->setupMockObj->getCurrencyCode());
    }

    /**
     * Test isFBEInstalled function
     * 
     * @return void
     */
    public function testIsFBEInstalled(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('isFBEInstalled')
            ->willReturn(true);

        $this->assertTrue($this->setupMockObj->isFBEInstalled(1));
    }

    /**
     * Test isFBEInstalled function
     * 
     * @return void
     */
    public function testIsFBEInstalledAssertFalse(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('isFBEInstalled')
            ->willReturn(false);

        $this->assertFalse($this->setupMockObj->isFBEInstalled(1));
    }

    /**
     * Test getCommerceExtensionIFrameURL function
     * 
     * @return void
     */
    public function testGetCommerceExtensionIFrameURL(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('getCommerceExtensionIFrameURL')
            ->willReturn('<iframe src="https://www.example.com" width="600" height="400" frameborder="0"></iframe>');

        $this->assertEquals(
            '<iframe src="https://www.example.com" width="600" height="400" frameborder="0"></iframe>',
            $this->setupMockObj->getCommerceExtensionIFrameURL(1)
        );
    }

    /**
     * Test hasCommerceExtensionIFramePermissionError function
     * 
     * @return void
     */
    public function testHasCommerceExtensionIFramePermissionError(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('hasCommerceExtensionPermissionError')
            ->willReturn(true);

        $this->assertTrue($this->setupMockObj->hasCommerceExtensionIFramePermissionError(1));
    }

    /**
     * Test hasCommerceExtensionIFramePermissionError function
     * 
     * @return void
     */
    public function testHasCommerceExtensionIFramePermissionErrorAssertFalse(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('commerceExtensionHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->commerceExtensionHelper);

        $this->commerceExtensionHelper->expects($this->once())
            ->method('hasCommerceExtensionPermissionError')
            ->willReturn(false);

        $this->assertFalse($this->setupMockObj->hasCommerceExtensionIFramePermissionError(1));
    }

    /**
     * Test getAppId function
     * 
     * @return void
     */
    public function testGetAppId(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('getAppId')
            ->willReturn('12wS$rt');

        $this->assertSame('12wS$rt', $this->setupMockObj->getAppId());
    }

    /**
     * Test getClientToken function
     * 
     * @return void
     */
    public function testGetClientToken(): void
    {
        $this->assertSame('52dcd04d6c7ed113121b5eb4be23b4a7', $this->setupMockObj->getClientToken());
    }

    /**
     * Test getAccessClientToken function
     * 
     * @return void
     */
    public function testGetAccessClientToken(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('getAppId')
            ->willReturn('12wS$rt');

        $this->assertSame('12wS$rt|52dcd04d6c7ed113121b5eb4be23b4a7', $this->setupMockObj->getAccessClientToken());
    }

    /**
     * Test removeKeyFromURL function
     * 
     * @dataProvider urlDataProvider
     * @return void
     */
    public function testRemoveKeyFromURL(string $currentUrl, string $expectedUpdatedUrl): void
    {
        $this->assertSame($expectedUpdatedUrl, $this->setupMockObj->removeKeyFromURL($currentUrl));
    }

    /**
     * Provides various URL scenarios and their expected updated URLs.
     *
     * @return array[]
     */
    public function urlDataProvider(): array
    {
        return [
            'with key and trailing slash' => [
                'currentUrl' => 'https://www.example.com/products/key/ABC123DEF/',
                'expectedUpdatedUrl' => 'https://www.example.com/products/',
            ],
            'with key and no trailing slash' => [
                'currentUrl' => 'https://www.example.com/category/key/GHI456JKL',
                'expectedUpdatedUrl' => 'https://www.example.com/category/',
            ],
            'with key in the middle' => [
                'currentUrl' => 'https://www.example.com/page/key/MNO789PQR/details',
                'expectedUpdatedUrl' => 'https://www.example.com/page/details',
            ],
            'no key present' => [
                'currentUrl' => 'https://www.example.com/services/id/123/info',
                'expectedUpdatedUrl' => 'https://www.example.com/services/id/123/info',
            ],
            'key at the beginning' => [
                'currentUrl' => 'https://www.example.com/key/STU901VWX/home',
                'expectedUpdatedUrl' => 'https://www.example.com/home',
            ]
        ];
    }

    /**
     * Test getSelectableStores function
     * 
     * @return void
     */
    public function testGetSelectableStores(): void
    {
        $storeId = 123;

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('admin');
        $storeList[$storeMock->getCode()] = $storeMock;
        
        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $this->assertNotSame($storeList, $this->setupMockObj->getSelectableStores());
    }

    /**
     * Test getSelectableStores function
     * 
     * @return void
     */
    public function testGetSelectableStoresEqual(): void
    {
        $storeId = 123;

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeList[$storeMock->getCode()] = $storeMock;
        
        $this->storeRepo->expects($this->once())
            ->method('getList')
            ->willReturn($storeList);

        $this->assertSame($storeList, $this->setupMockObj->getSelectableStores());
    }

    /**
     * Test getFbeInstallsConfigUrl function
     * 
     * @return void
     */
    public function testGetFbeInstallsConfigUrl(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbeinstallsconfig')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbeinstallsconfig');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/fbeinstallsconfig', $this->setupMockObj->getFbeInstallsConfigUrl());
    }

    /**
     * Test getFbeInstallsSaveUrl function
     * 
     * @return void
     */
    public function testGetFbeInstallsSaveUrl(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbeinstallssave')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbeinstallssave');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/fbeinstallssave', $this->setupMockObj->getFbeInstallsSaveUrl());
    }

    /**
     * Test getStoreId function
     * 
     * @return void
     */
    public function testGetStoreId(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(1);

        $this->assertSame(1, $this->setupMockObj->getStoreId());
    }

    /**
     * Test getInstalledFeaturesAjaxRouteUrl function
     * 
     * @return void
     */
    public function testGetInstalledFeaturesAjaxRouteUrl(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/fbinstalledfeatures')
            ->willReturn('https://facebook.com/fbeadmin/ajax/fbinstalledfeatures');

        $this->assertSame('https://facebook.com/fbeadmin/ajax/fbinstalledfeatures', $this->setupMockObj->getInstalledFeaturesAjaxRouteUrl());
    }

    /**
     * Test getWebsiteId function
     * 
     * @return void
     */
    public function testGetWebsiteId(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('request');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->requestInterface);

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('website')
            ->willReturn(1);

        $this->assertSame(1, $this->setupMockObj->getWebsiteId());
    }

    /**
     * Test getDefaultStoreViewId function
     * 
     * @return void
     */
    public function testGetDefaultStoreViewId(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');

        $this->fbeHelper->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->assertSame($storeMock->getId(), $this->setupMockObj->getDefaultStoreViewId());
    }

    /**
     * Test upsertApiKey function
     * 
     * @return void
     */
    public function testUpsertApiKey(): void
    {
        $apiKey = 'sample-api-key';
        $this->apiKeyService->method('upsertApiKey')
            ->willReturn($apiKey);
        $this->assertEquals($apiKey, $this->setupMockObj->upsertApiKey());
    }

    /**
     * Test getCommercePartnerSellerPlatformType function
     * 
     * @return void
     */
    public function testGetCommercePartnerSellerPlatformType(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('adobeConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->adobeCloudConfigInterface);

        $this->adobeCloudConfigInterface->expects($this->once())
            ->method('getCommercePartnerSellerPlatformType')
            ->willReturn('premise');

        $this->assertSame('premise', $this->setupMockObj->getCommercePartnerSellerPlatformType());
    }

    /**
     * Test getCustomApiKey function
     * 
     * @return void
     */
    public function testGetCustomApiKey(): void
    {
        $apiKey = 'sample-api-key';
        $this->apiKeyService->method('getCustomApiKey')
            ->willReturn($apiKey);
        $this->assertEquals($apiKey, $this->setupMockObj->getCustomApiKey());
    }

    /**
     * Test getRepairRepairCommercePartnerIntegrationAjaxRoute function
     * 
     * @return void
     */
    public function testGetRepairRepairCommercePartnerIntegrationAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/RepairCommercePartnerIntegration')
            ->willReturn('https://facebook.com/fbeadmin/ajax/RepairCommercePartnerIntegration');

        $this->assertSame(
            'https://facebook.com/fbeadmin/ajax/RepairCommercePartnerIntegration',
            $this->setupMockObj->getRepairRepairCommercePartnerIntegrationAjaxRoute()
        );
    }

    /**
     * Test getUpdateMBEConfigAjaxRoute function
     * 
     * @return void
     */
    public function testGetUpdateMBEConfigAjaxRoute(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->fbeHelper);

        $this->fbeHelper->expects($this->once())
            ->method('getUrl')
            ->with('fbeadmin/ajax/MBEUpdateInstalledConfig')
            ->willReturn('https://facebook.com/fbeadmin/ajax/MBEUpdateInstalledConfig');

        $this->assertSame(
            'https://facebook.com/fbeadmin/ajax/MBEUpdateInstalledConfig',
            $this->setupMockObj->getUpdateMBEConfigAjaxRoute()
        );
    }

    /**
     * Test getStoreTimezone function
     * 
     * @return void
     */
    public function testGetStoreTimezone(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('scopeConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->scopeConfig);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Setup::TIMEZONE_CONFIG_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('UTC');

        $this->assertSame('UTC', $this->setupMockObj->getStoreTimezone());
    }

    /**
     * Test getStoreCountryCode function
     * 
     * @return void
     */
    public function testGetStoreCountryCode(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('scopeConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->scopeConfig);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Setup::COUNTRY_CONFIG_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('US');

        $this->assertSame('US', $this->setupMockObj->getStoreCountryCode());
    }

    /**
     * Test getStoreBaseUrl function
     * 
     * @return void
     */
    public function testGetStoreBaseUrl(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('storeManager');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->storeManager);

        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');
        $storeMock->method('getBaseUrl')->willReturn('https://facebook.com/');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->assertSame('https://facebook.com/', $this->setupMockObj->getStoreBaseUrl());
    }

    /**
     * Test getExtensionVersion function
     * 
     * @return void
     */
    public function testGetExtensionVersion(): void
    {
        $reflection = new \ReflectionClass(Setup::class);
        $configModulesProperty = $reflection->getProperty('systemConfig');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->setupMockObj, $this->systemConfig);

        $this->systemConfig->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('v1.4.3');

        $this->assertSame('v1.4.3', $this->setupMockObj->getExtensionVersion());
    }
}
