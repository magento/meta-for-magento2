<?php

declare(strict_types=1);

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\BusinessExtension\Test\Unit\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Helper\GraphAPIConfig;
use Meta\BusinessExtension\Logger\Logger;
use Meta\BusinessExtension\Model\System\Config;
use PHPUnit\Framework\TestCase;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store as CoreStore;
use Magento\Framework\Phrase;
use FacebookAds\Object\ServerSide\AdsPixelSettings;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class FBEHelperTest extends TestCase
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var Config
     */
    private Config $systemConfig;

    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManagerInterface;

    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $productMetaData;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerInterface = $this->createMock(ObjectManagerInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->systemConfig = $this->createMock(Config::class);
        $this->productMetaData = $this->createMock(ProductMetadataInterface::class);
        $this->graphAPIConfig = $this->createMock(GraphAPIConfig::class);
        $this->graphAPIAdapter = $this->createMock(GraphAPIAdapter::class);

        $this->fbeHelper = new FBEHelper(
            $this->objectManagerInterface,
            $this->logger,
            $this->storeManager,
            $this->systemConfig,
            $this->productMetaData,
            $this->graphAPIConfig,
            $this->graphAPIAdapter
        );
    }

    /**
     * Test partner agent is correct
     *
     * @return void
     */
    public function testCorrectPartnerAgent(): void
    {
        $magentoVersion = '2.3.5';
        $pluginVersion = '1.0.0';
        $source = $this->fbeHelper->getSource();
        $productMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion', 'getEdition', 'getName'])
            ->getMock();
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn($magentoVersion);
        $this->objectManagerInterface->method('get')->willReturn($productMetadata);
        $this->systemConfig->method('getModuleVersion')->willReturn($pluginVersion);
        $this->assertEquals(
            sprintf('%s-%s-%s', $source, $magentoVersion, $pluginVersion),
            $this->fbeHelper->getPartnerAgent(true)
        );
    }

    /**
     * Test getGraphAPIAdapter function
     *
     * @return void
     */
    public function testGetGraphAPIAdapter(): void
    {
        $this->assertEquals($this->graphAPIAdapter, $this->fbeHelper->getGraphAPIAdapter());
    }

    /**
     * Test getGraphBaseURL function
     *
     * @return void
     */
    public function testGetGraphBaseURL(): void
    {
        $this->graphAPIConfig->expects($this->once())
            ->method('getGraphBaseURL')
            ->willReturn('https://facebook.com/');
        $this->assertEquals('https://facebook.com/', $this->fbeHelper->getGraphBaseURL());
    }

    /**
     * Test getUrl function
     *
     * @return void
     */
    public function testGetUrl(): void
    {
        $partialURL = 'feed/upload';
        $urlInterface = $this->createMock(\Magento\Backend\Model\UrlInterface::class);
        $this->objectManagerInterface->expects($this->once())
            ->method('get')
            ->with(\Magento\Backend\Model\UrlInterface::class)
            ->willReturn($urlInterface);
        $urlInterface->expects($this->once())
            ->method('getUrl')
            ->willReturn('https://facebook.com/' . $partialURL);
        $this->assertEquals('https://facebook.com/' . $partialURL, $this->fbeHelper->getUrl($partialURL));
    }

    /**
     * Test checkAdminEndpointPermission function
     *
     * @return void
     */
    public function testCheckAdminEndpointPermission(): void
    {
        $status = new DataObject(['status' => 1]);
        $adminSessionManager = $this->createMock(AdminSessionsManager::class);
        $adminSessionManager->expects($this->once())
            ->method('getCurrentSession')
            ->willReturn($status);
        $this->objectManagerInterface->expects($this->once())
            ->method('create')
            ->with(AdminSessionsManager::class)
            ->willReturn($adminSessionManager);
        
        $this->fbeHelper->checkAdminEndpointPermission();
    }

    /**
     * Test checkAdminEndpointPermission function
     *
     * @return void
     */
    public function testCheckAdminEndpointPermissionWithException(): void
    {
        $status = new DataObject(['status' => 0]);
        $adminSessionManager = $this->createMock(AdminSessionsManager::class);
        $adminSessionManager->expects($this->once())
            ->method('getCurrentSession')
            ->willReturn($status);
        $this->objectManagerInterface->expects($this->once())
            ->method('create')
            ->with(AdminSessionsManager::class)
            ->willReturn($adminSessionManager);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((new Phrase('This endpoint is for logged in admin and ajax only.'))->render());
        
        $this->fbeHelper->checkAdminEndpointPermission();
    }

    /**
     * Test genUniqueTraceID function
     *
     * @return void
     */
    public function testGenUniqueTraceIDAssertString(): void
    {
        $this->assertIsString($this->fbeHelper->genUniqueTraceID());
    }

    /**
     * Test genUniqueTraceID function
     *
     * @return void
     */
    public function testGenUniqueTraceIDAssertStartWithString(): void
    {
        $this->assertStringStartsWith('magento_', $this->fbeHelper->genUniqueTraceID());
    }

    /**
     * Test getCurrentTimeInMS function
     *
     * @return void
     */
    public function testGetCurrentTimeInMS(): void
    {
        $this->assertIsInt($this->fbeHelper->getCurrentTimeInMS());
    }

    /**
     * Test fetchAndSaveAAMSettings function
     *
     * @return void
     */
    public function testFetchAndSaveAAMSettings(): void
    {
        $this->assertNull($this->fbeHelper->fetchAndSaveAAMSettings('1', 1));
    }

    /**
     * Test getAAMSettings function
     *
     * @return void
     */
    public function testGetAAMSettingsWithNull(): void
    {
        $this->assertNull($this->fbeHelper->getAAMSettings('1', 1));
    }

    /**
     * Test getStore function
     *
     * @return void
     */
    public function testGetStore(): void
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Website 1');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        
        $this->assertEquals($storeMock, $this->fbeHelper->getStore());
    }

    /**
     * Test getStore function
     *
     * @return void
     */
    public function testGetStoreWithDefaultStore(): void
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Website 1');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn(null);

        $this->storeManager->expects($this->once())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);
        
        $this->assertEquals($storeMock, $this->fbeHelper->getStore());
    }

    /**
     * Test getBaseUrl function
     *
     * @return void
     */
    public function testGetBaseUrl(): void
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Website 1');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');
        $storeMock->method('getBaseUrl')->willReturn('https://facebook.com/');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        
        $this->assertEquals('https://facebook.com/', $this->fbeHelper->getBaseUrl());
    }

    /**
     * Test getFBEExternalBusinessId function
     *
     * @return void
     */
    public function testGetFBEExternalBusinessId(): void
    {
        $storeId = 99;
        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->with($storeId)
            ->willReturn('23456');
        
        $this->assertEquals('23456', $this->fbeHelper->getFBEExternalBusinessId($storeId));
    }

    /**
     * Test getFBEExternalBusinessId function
     *
     * @return void
     */
    public function testGetFBEExternalBusinessIdWithNullSystemBusinessId(): void
    {
        $storeId = 99;
        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->with($storeId)
            ->willReturn(null);
        
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Website 1');
        $storeMock->method('getId')->willReturn($storeId);
        $storeMock->method('getCode')->willReturn('admin');
        $storeMock->method('getBaseUrl')->willReturn('https://facebook.com/');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with("Store id---" . $storeId, [])
            ->willReturnSelf();
        $this->assertStringStartsWith('fbe_magento_' . $storeId, $this->fbeHelper->getFBEExternalBusinessId($storeId));
    }

    /**
     * Test log function
     *
     * @return void
     */
    public function testLog(): void
    {
        $info = "Test log";
        $context = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with($info, $context)
            ->willReturnSelf();
        $this->fbeHelper->log($info, $context);
    }

    /**
     * Test log function
     *
     * @return void
     */
    public function testLogWithLogType(): void
    {
        $storeId = 99;
        $info = "Test log";
        $context = ['log_type' => 'Error', 'store_id' => $storeId, 'extra_data' => ['log data']];
        $this->logger->expects($this->once())
            ->method('info')
            ->willReturnSelf();
        $this->systemConfig->expects($this->once())
            ->method('getCommerceAccountId')
            ->with($storeId)
            ->willReturn('23456');
        $this->systemConfig->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('v1.4.3');
        $this->productMetaData->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.6.7');
        $this->fbeHelper->log($info, $context);
    }

    /**
     * Test logCritical function
     *
     * @return void
     */
    public function testLogCritical(): void
    {
        $storeId = 99;
        $info = "Test log";
        $context = ['log_type' => 'Error', 'store_id' => $storeId, 'extra_data' => ['log data']];
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($info, $context)
            ->willReturnSelf();
        $this->fbeHelper->logCritical($info, $context);
    }

    /**
     * Test logException function
     *
     * @return void
     */
    public function testLogException(): void
    {
        $context = ['log_type' => 'Error', 'extra_data' => ['log data']];
        $exception = new \Exception("Test exception");

        $this->logger->expects($this->once())
            ->method('error')
            ->willReturnSelf();
        $this->systemConfig->method('getModuleVersion')->willReturn('v1.4.3');
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn('2.4.8');
        $this->fbeHelper->logException($exception, $context);
    }

    /**
     * Test logException function
     *
     * @return void
     */
    public function testLogExceptionWithStoreId(): void
    {
        $context = ['log_type' => 'Error', 'store_id' => 99];
        $exception = new \Exception("Test exception");

        $this->logger->expects($this->once())
            ->method('error')
            ->willReturnSelf();
        $this->systemConfig->method('getModuleVersion')->willReturn('v1.4.3');
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn('2.4.8');
        $this->fbeHelper->logException($exception, $context);
    }

    /**
     * Test logException function
     *
     * @return void
     */
    public function testLogExceptionWithoutLogType(): void
    {
        $context = ['store_id' => 99, 'extra_data' => ['log data']];
        $exception = new \Exception("Test exception");

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->willReturnSelf();
        $this->fbeHelper->logException($exception, $context);
    }

    /**
     * Test logExceptionImmediatelyToMeta function
     *
     * @return void
     */
    public function testLogExceptionImmediatelyToMeta(): void
    {
        $context = ['store_id' => 99, 'extra_data' => ['log data']];
        $exception = new \Exception("Test exception");

        $this->logger->expects($this->once())
            ->method('error')
            ->willReturnSelf();
        $this->systemConfig->method('getModuleVersion')->willReturn('v1.4.3');
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn('2.4.8');

        $this->fbeHelper->logExceptionImmediatelyToMeta($exception, $context);
    }

    /**
     * Test logExceptionDetailsImmediatelyToMeta function
     *
     * @return void
     */
    public function testLogExceptionDetailsImmediatelyToMeta(): void
    {
        $context = ['store_id' => 99, 'extra_data' => ['log data']];
        $exception = new \Exception("Test exception");

        $this->logger->expects($this->once())
            ->method('error')
            ->willReturnSelf();
        $this->systemConfig->method('getModuleVersion')->willReturn('v1.4.3');
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn('2.4.8');

        $this->fbeHelper->logExceptionDetailsImmediatelyToMeta($exception->getCode(), $exception->getMessage(), $exception->getTraceAsString(), $context);
    }

    /**
     * Test logTelemetryToMeta function
     *
     * @return void
     */
    public function testLogTelemetryToMeta(): void
    {
        $context = ['store_id' => 99, 'extra_data' => ['log data']];
        $exception = new \Exception("Test exception");

        $this->logger->expects($this->once())
            ->method('info')
            ->willReturnSelf();
        $this->systemConfig->method('getModuleVersion')->willReturn('v1.4.3');
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn('2.4.8');

        $this->fbeHelper->logTelemetryToMeta($exception->getMessage(), $context);
    }

    /**
     * Test logPixelEvent function
     *
     * @return void
     */
    public function testLogPixelEvent(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->willReturnSelf();
        $this->systemConfig->method('getModuleVersion')->willReturn('v1.4.3');

        $this->fbeHelper->logPixelEvent('a12245b^', 'add_to_cart');
    }

    /**
     * Test getStoreCurrencyCode function
     *
     * @return void
     */
    public function testGetStoreCurrencyCodeWithDefaultStore(): void
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getCurrentCurrencyCode')->willReturn('USD');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn(null);

        $this->storeManager->expects($this->once())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);
        
        $this->assertEquals("USD", $this->fbeHelper->getStoreCurrencyCode());
    }

    /**
     * Test getStoreCurrencyCode function
     *
     * @return void
     */
    public function testGetStoreCurrencyCode(): void
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getCurrentCurrencyCode')->willReturn('USD');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        
        $this->assertEquals("USD", $this->fbeHelper->getStoreCurrencyCode());
    }

    /**
     * Test getAAMSettings function
     *
     * @return void
     */
    public function testGetAAMSettings(): void
    {
        $settingsAsString = '{"pixelId": 12345, "enableAutomaticMatching": true, "enabledAutomaticMatchingFields": ["email", "firstName"]}';
        $this->systemConfig->expects($this->once())
            ->method('getPixelAamSettings')
            ->willReturn($settingsAsString);
            
        $adsPixelSettings = $this->createMock(AdsPixelSettings::class);
        $this->assertInstanceOf(AdsPixelSettings::class, $this->fbeHelper->getAAMSettings());
    }

    /**
     * Test saveAAMSettings function
     *
     * @return void
     */
    public function testSaveAAMSettings(): void
    {
        $storeId = 999;
        $settingsAsString = '{"enableAutomaticMatching":true,"enabledAutomaticMatchingFields":["email","firstName"],"pixelId":12345}';
        $settings = new AdsPixelSettings();
        $settings->setPixelId(12345);
        $settings->setEnableAutomaticMatching(true);
        $settings->setEnabledAutomaticMatchingFields(["email", "firstName"]);

        $this->systemConfig->expects($this->once())
            ->method('saveConfig')
            ->with(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS, $settingsAsString, $storeId)
            ->willReturnSelf();
            
        $adsPixelSettings = $this->createMock(AdsPixelSettings::class);

        $reflection = new \ReflectionClass(FBEHelper::class);
        $handler = $reflection->getMethod('saveAAMSettings');
        $handler->setAccessible(true);

        $this->assertSame($settingsAsString, $handler->invoke($this->fbeHelper, $settings, $storeId));
    }
}
