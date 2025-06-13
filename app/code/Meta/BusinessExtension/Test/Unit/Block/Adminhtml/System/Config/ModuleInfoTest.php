<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Block\Adminhtml\System\Config;

use Meta\BusinessExtension\Block\Adminhtml\System\Config;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Block\Adminhtml\System\Config\ModuleInfo;
use Magento\Framework\App\RequestInterface;

class ModuleInfoTest extends TestCase
{
    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->apiKeyService = $this->createMock(ApiKeyService::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $objectManager = new ObjectManager($this);
        $this->moduleInfoMockObj = $objectManager->getObject(
            ModuleInfo::class,
            [
                'context' => $context,
                'systemConfig' => $this->systemConfig,
                'apiKeyService' => $this->apiKeyService,
                'data' => []
            ]
        );
    }

    /**
     * Test getModuleVersion function
     * 
     * @return void
     */
    public function testGetModuleVersion(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('v1.4.5');

        $this->assertEquals('v1.4.5', $this->moduleInfoMockObj->getModuleVersion());
    }

    /**
     * Test getStoreId function
     * 
     * @return void
     */
    public function testGetStoreId(): void
    {
        $storeId = 123;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
        
        $this->assertEquals($storeId, $this->moduleInfoMockObj->getStoreId());
    }

    /**
     * Test getStoreId function
     * 
     * @return void
     */
    public function testGetStoreIdWillReturnDefaultStoreId(): void
    {
        $storeId = 123;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(null);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with(null)
            ->willReturn(null);

        $this->systemConfig->expects($this->once())
            ->method('getDefaultStoreId')
            ->willReturn($storeId);
        
        $this->assertEquals($storeId, $this->moduleInfoMockObj->getStoreId());
    }

    /**
     * Test getPixelId function
     * 
     * @return void
     */
    public function testGetPixelId(): void
    {
        $pixelId = '123qaw!#^';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getPixelId')
            ->with($storeId)
            ->willReturn($pixelId);
        
        $this->assertEquals($pixelId, $this->moduleInfoMockObj->getPixelId());
    }

    /**
     * Test getAutomaticMatchingStatus function
     * 
     * @return void
     */
    public function testGetAutomaticMatchingStatus(): void
    {
        $pixelId = '123qaw!#^';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getPixelAamSettings')
            ->with($storeId)
            ->willReturn(null);
        
        $this->assertEquals('N/A', $this->moduleInfoMockObj->getAutomaticMatchingStatus());
    }

    /**
     * Test getAutomaticMatchingStatus function
     * 
     * @return void
     */
    public function testGetAutomaticMatchingStatusWillReturnString(): void
    {
        $pixelId = '123qaw!#^';
        $storeId = 123;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getPixelAamSettings')
            ->with($storeId)
            ->willReturn('{"enableAutomaticMatching":1}');
        
        $this->assertEquals('Enabled', $this->moduleInfoMockObj->getAutomaticMatchingStatus());
    }

    /**
     * Test getAutomaticMatchingHelpCenterArticleLink function
     * 
     * @return void
     */
    public function testGetAutomaticMatchingHelpCenterArticleLink(): void
    {
        $this->assertEquals('https://www.facebook.com/business/help/611774685654668', $this->moduleInfoMockObj->getAutomaticMatchingHelpCenterArticleLink());
    }

    /**
     * Test getCommerceAccountId function
     * 
     * @return void
     */
    public function testGetCommerceAccountId(): void
    {
        $commerceAccountId = '123qaw!#^';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getCommerceAccountId')
            ->with($storeId)
            ->willReturn($commerceAccountId);
        
        $this->assertEquals($commerceAccountId, $this->moduleInfoMockObj->getCommerceAccountId());
    }

    /**
     * Test getPageId function
     * 
     * @return void
     */
    public function testGetPageId(): void
    {
        $pageId = '123qaw!#^';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getPageId')
            ->with($storeId)
            ->willReturn($pageId);
        
        $this->assertEquals($pageId, $this->moduleInfoMockObj->getPageId());
    }

    /**
     * Test getCatalogId function
     * 
     * @return void
     */
    public function testGetCatalogId(): void
    {
        $catalogId = '523';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getCatalogId')
            ->with($storeId)
            ->willReturn($catalogId);
        
        $this->assertEquals($catalogId, $this->moduleInfoMockObj->getCatalogId());
    }

    /**
     * Test getCommerceManagerUrl function
     * 
     * @return void
     */
    public function testGetCommerceManagerUrl(): void
    {
        $commerceManagerUrl = 'https://www.facebook.com/business/';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getCommerceManagerUrl')
            ->with($storeId)
            ->willReturn($commerceManagerUrl);
        
        $this->assertEquals($commerceManagerUrl, $this->moduleInfoMockObj->getCommerceManagerUrl());
    }

    /**
     * Test getCatalogManagerUrl function
     * 
     * @return void
     */
    public function testGetCatalogManagerUrl(): void
    {
        $catalogManagerUrl = 'https://www.facebook.com/business/catalog/';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getCatalogManagerUrl')
            ->with($storeId)
            ->willReturn($catalogManagerUrl);
        
        $this->assertEquals($catalogManagerUrl, $this->moduleInfoMockObj->getCatalogManagerUrl());
    }

    /**
     * Test getSupportUrl function
     * 
     * @return void
     */
    public function testGetSupportUrl(): void
    {
        $supportUrl = 'https://www.facebook.com/business/support/';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getSupportUrl')
            ->with($storeId)
            ->willReturn($supportUrl);
        
        $this->assertEquals($supportUrl, $this->moduleInfoMockObj->getSupportUrl());
    }

    /**
     * Test getAPIToken function
     * 
     * @return void
     */
    public function testGetAPIToken(): void
    {
        $apiKey = 'key_*^ghT@!123';
        $storeId = 123;

        $this->apiKeyService->expects($this->once())
            ->method('getCustomApiKey')
            ->willReturn($apiKey);
        
        $this->assertEquals($apiKey, $this->moduleInfoMockObj->getAPIToken());
    }

    /**
     * Test isFBEInstalled function
     * 
     * @return void
     */
    public function testIsFBEInstalledWithFalse(): void
    {
        $isFbInstalled = false;
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('isFBEInstalled')
            ->with($storeId)
            ->willReturn($isFbInstalled);
        
        $this->assertFalse($this->moduleInfoMockObj->isFBEInstalled());
    }

    /**
     * Test isFBEInstalled function
     * 
     * @return void
     */
    public function testIsFBEInstalledWithTrue(): void
    {
        $isFbInstalled = true;
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('isFBEInstalled')
            ->with($storeId)
            ->willReturn($isFbInstalled);
        
        $this->assertTrue($this->moduleInfoMockObj->isFBEInstalled());
    }

    /**
     * Test isDebugMode function
     * 
     * @return void
     */
    public function testIsDebugModeTrue(): void
    {
        $isDebug = true;
            
        $this->systemConfig->expects($this->once())
            ->method('isDebugMode')
            ->willReturn($isDebug);
        
        $this->assertTrue($this->moduleInfoMockObj->isDebugMode());
    }

    /**
     * Test isDebugMode function
     * 
     * @return void
     */
    public function testIsDebugModeFalse(): void
    {
        $isDebug = false;
            
        $this->systemConfig->expects($this->once())
            ->method('isDebugMode')
            ->willReturn($isDebug);
        
        $this->assertFalse($this->moduleInfoMockObj->isDebugMode());
    }

    /**
     * Test getCommercePartnerIntegrationId function
     * 
     * @return void
     */
    public function testGetCommercePartnerIntegrationId(): void
    {
        $commercePartnerIntegrationId = 'rtpkl%4##!k';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getCommercePartnerIntegrationId')
            ->with($storeId)
            ->willReturn($commercePartnerIntegrationId);
        
        $this->assertSame($commercePartnerIntegrationId, $this->moduleInfoMockObj->getCommercePartnerIntegrationId());
    }

    /**
     * Test getExternalBusinessID function
     * 
     * @return void
     */
    public function testGetExternalBusinessID(): void
    {
        $externalBusinessId = 'rtpkl%4##!k';
        $storeId = 123;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn((string) $storeId);
        
        $this->systemConfig->expects($this->once())
            ->method('castStoreIdAsInt')
            ->with((string) $storeId)
            ->willReturn($storeId);
            
        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessID')
            ->with($storeId)
            ->willReturn($externalBusinessId);
        
        $this->assertSame($externalBusinessId, $this->moduleInfoMockObj->getExternalBusinessID());
    }

    /**
     * Test shouldShowStoreLevelConfig function
     * 
     * @return void
     */
    public function testShouldShowStoreLevelConfig(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isSingleStoreMode')
            ->willReturn(true);
        
        $this->assertTrue($this->moduleInfoMockObj->shouldShowStoreLevelConfig());
    }

    /**
     * Test shouldShowStoreLevelConfig function
     * 
     * @return void
     */
    public function testShouldShowStoreLevelConfigNotSingleStoreMode(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isSingleStoreMode')
            ->willReturn(false);
        
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(null);
        
        $this->assertFalse($this->moduleInfoMockObj->shouldShowStoreLevelConfig());
    }

    /**
     * Test shouldShowStoreLevelConfig function
     * 
     * @return void
     */
    public function testShouldShowStoreLevelConfigNotSingleStoreModeWithStoreParam(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('isSingleStoreMode')
            ->willReturn(false);
        
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(123);
        
        $this->assertTrue($this->moduleInfoMockObj->shouldShowStoreLevelConfig());
    }
}