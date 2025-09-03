<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model;

use Meta\BusinessExtension\Model\MBEInstalls;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\CatalogConfigUpdateHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Psr\Log\LoggerInterface;
use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store as CoreStore;

class MBEInstallsTest extends TestCase
{
    /**
     * Class Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $object = new ObjectManager($this);
        $this->fbHelper = $object->getObject(FBEHelper::class);

        $this->fbHelperMock = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->graphApiAdapter = $this->createMock(GraphAPIAdapter::class);
        $this->facebookInstalledFeature = $this->createMock(FacebookInstalledFeature::class);
        $this->catalogConfigUpdateHelper = $this->createMock(CatalogConfigUpdateHelper::class);
        $this->apiKeyService = $this->createMock(ApiKeyService::class);
        $this->storeManagerInterface = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->adobeCloudConfig = $this->createMock(AdobeCloudConfigInterface::class);

        $this->mbeIntallMockObj = new MBEInstalls(
            $this->fbHelper,
            $this->systemConfig,
            $this->graphApiAdapter,
            $this->facebookInstalledFeature,
            $this->catalogConfigUpdateHelper,
            $this->apiKeyService,
            $this->storeManagerInterface,
            $this->logger,
            $this->adobeCloudConfig
        );
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSave(): void
    {
        $pixelId = '9876543210';
        $catalogId = 'cat_121';
        $onsiteEligible = false;
        $commercePartnerIntegrationId = 'integration_id_1122';
        $storeId = 909090;
        $logContent = 'Log Data';

        $response = [
            0 => [
                'pixel_id' => $pixelId,
                'catalog_id' => $catalogId,
                'onsite_eligible' => $onsiteEligible,
                'commerce_partner_integration_id' => $commercePartnerIntegrationId
            ]
        ];

        $this->catalogConfigUpdateHelper
            ->expects($this->once())
            ->method('updateCatalogConfiguration')
            ->with(
                $storeId,
                $catalogId,
                $commercePartnerIntegrationId,
                $pixelId,
                false
            );
        $this->systemConfig
            ->method('saveConfig')
            ->willReturn('');
        $this->assertTrue($this->mbeIntallMockObj->save($response, $storeId));
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSaveWithValidFBID(): void
    {
        $pixelId = 'pixel_1';
        $catalogId = 'cat_121';
        $onsiteEligible = false;
        $commercePartnerIntegrationId = 'integration_id_1122';
        $storeId = 909090;
        $logContent = 'Log Data';

        $response = [
            0 => [
                'pixel_id' => $pixelId,
                'catalog_id' => $catalogId,
                'onsite_eligible' => $onsiteEligible,
                'commerce_partner_integration_id' => $commercePartnerIntegrationId
            ]
        ];

        $this->catalogConfigUpdateHelper
            ->expects($this->once())
            ->method('updateCatalogConfiguration')
            ->with(
                $storeId,
                $catalogId,
                $commercePartnerIntegrationId,
                $pixelId,
                false
            );
        $this->systemConfig
            ->method('saveConfig')
            ->willReturn('');
        $this->assertTrue($this->mbeIntallMockObj->save($response, $storeId));
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSaveWithProfile(): void
    {
        $pixelId = 'pixel_1';
        $catalogId = 'cat_121';
        $onsiteEligible = false;
        $commercePartnerIntegrationId = 'integration_id_1122';
        $storeId = 909090;
        $logContent = 'Log Data';

        $response = [
            0 => [
                'pixel_id' => $pixelId,
                'profiles' => [
                    'profile_1',
                    'profile_2'
                ],
                'catalog_id' => $catalogId,
                'onsite_eligible' => $onsiteEligible,
                'commerce_partner_integration_id' => $commercePartnerIntegrationId
            ]
        ];

        $this->catalogConfigUpdateHelper
            ->expects($this->once())
            ->method('updateCatalogConfiguration')
            ->with(
                $storeId,
                $catalogId,
                $commercePartnerIntegrationId,
                $pixelId,
                false
            );
        $this->systemConfig
            ->method('saveConfig')
            ->willReturn('');
        $this->assertTrue($this->mbeIntallMockObj->save($response, $storeId));
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSaveWithPages(): void
    {
        $pixelId = 'pixel_1';
        $catalogId = 'cat_121';
        $onsiteEligible = false;
        $commercePartnerIntegrationId = 'integration_id_1122';
        $storeId = 909090;
        $logContent = 'Log Data';
        $accessToken = '[[]][[****|||TESTTOKEN';

        $response = [
            0 => [
                'pixel_id' => $pixelId,
                'profiles' => [
                    'profile_1',
                    'profile_2'
                ],
                'pages' => [
                    'page_1',
                    'page_2'
                ],
                'catalog_id' => $catalogId,
                'onsite_eligible' => $onsiteEligible,
                'commerce_partner_integration_id' => $commercePartnerIntegrationId
            ]
        ];
        $this->systemConfig
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->graphApiAdapter
            ->method('getPageAccessToken')
            ->with(
                $this->equalTo($accessToken),
                $this->equalTo('page_1')
            )
            ->willReturn($accessToken);

        $this->catalogConfigUpdateHelper
            ->expects($this->once())
            ->method('updateCatalogConfiguration')
            ->with(
                $storeId,
                $catalogId,
                $commercePartnerIntegrationId,
                $pixelId,
                false
            );
        $this->systemConfig
            ->method('saveConfig')
            ->willReturn('');
            
        $this->assertTrue($this->mbeIntallMockObj->save($response, $storeId));
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSaveWithPagesWithException(): void
    {
        $pixelId = 'pixel_1';
        $catalogId = 'cat_121';
        $onsiteEligible = false;
        $commercePartnerIntegrationId = 'integration_id_1122';
        $storeId = 909090;
        $logContent = 'Log Data';

        $response = [
            0 => [
                'pixel_id' => $pixelId,
                'profiles' => [
                    'profile_1',
                    'profile_2'
                ],
                'pages' => [
                    'page_1',
                    'page_2'
                ],
                'catalog_id' => $catalogId,
                'onsite_eligible' => $onsiteEligible,
                'commerce_partner_integration_id' => $commercePartnerIntegrationId
            ]
        ];

        $this->catalogConfigUpdateHelper
            ->expects($this->once())
            ->method('updateCatalogConfiguration')
            ->with(
                $storeId,
                $catalogId,
                $commercePartnerIntegrationId,
                $pixelId,
                false
            );
        $this->systemConfig
            ->method('saveConfig')
            ->willReturn('');
         $this->expectException(LocalizedException::class);
        $this->assertTrue($this->mbeIntallMockObj->save($response, $storeId));
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSaveEmptyData(): void
    {
        $storeId = 1;
        $response = [];

        $this->assertFalse($this->mbeIntallMockObj->save($response, $storeId));
    }

    /**
     * Test saveCommercePartnerIntegrationId function
     *
     * @return void
     */
    public function testSaveCommercePartnerIntegrationId(): void
    {
        $storeId = 9999;
        $commercePartnerIntegrationId = 99999;
        $this->systemConfig
            ->method('saveConfig')
            ->with(
                $this->equalTo(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_PARTNER_INTEGRATION_ID),
                $this->equalTo($commercePartnerIntegrationId),
                $this->equalTo($storeId)
            )
            ->willReturn('');
        
        $result = $this->mbeIntallMockObj->saveCommercePartnerIntegrationId($commercePartnerIntegrationId, $storeId);

        $this->assertInstanceOf(MBEInstalls::class, $result);
    }

    /**
     * Test saveIsOnsiteEligible function
     *
     * @return void
     */
    public function testSaveIsOnsiteEligibleWithOnSiteEligibleTrue(): void
    {
        $onsiteEligible = true;
        $storeId = 99999;
        $this->systemConfig
            ->method('saveConfig')
            ->with(
                $this->equalTo(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_IS_ONSITE_ELIGIBLE),
                $this->equalTo($onsiteEligible),
                $this->equalTo($storeId)
            )
            ->willReturn('');
        
        $result = $this->mbeIntallMockObj->saveIsOnsiteEligible($onsiteEligible, $storeId);

        $this->assertInstanceOf(MBEInstalls::class, $result);
    }

    /**
     * Test saveIsOnsiteEligible function
     *
     * @return void
     */
    public function testSaveIsOnsiteEligibleWithOnSiteEligibleFalse(): void
    {
        $onsiteEligible = false;
        $storeId = 99999;
        $this->systemConfig
            ->method('saveConfig')
            ->with(
                $this->equalTo(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_IS_ONSITE_ELIGIBLE),
                $this->equalTo($onsiteEligible),
                $this->equalTo($storeId)
            )
            ->willReturn('');
        
        $result = $this->mbeIntallMockObj->saveIsOnsiteEligible($onsiteEligible, $storeId);

        $this->assertInstanceOf(MBEInstalls::class, $result);
    }

    /**
     * Test setInstalledFlag function
     *
     * @return void
     */
    public function testSetInstalledFlag(): void
    {
        $storeId = 9999;

        $this->systemConfig
            ->method('saveConfig')
            ->with(
                $this->equalTo(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED),
                $this->equalTo(true),
                $this->equalTo($storeId)
            )
            ->willReturn('');

        $this->mbeIntallMockObj->setInstalledFlag($storeId);
    }

    /**
     * Test updateMBESettings function
     *
     * @return void
     */
    public function testUpdateMBESettings(): void
    {
        $storeId = 9999;
        $accessToken = '[[]][[****|||TESTTOKEN';
        $businessId = 1212121212;
        $response = ['data' => []];

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($accessToken);
        
        $this->systemConfig
            ->method('getExternalBusinessId')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($businessId);

        $this->graphApiAdapter
            ->method('getFBEInstalls')
            ->with(
                $this->equalTo($accessToken),
                $this->equalTo($businessId)
            )
            ->willReturn($response);

        $this->mbeIntallMockObj->updateMBESettings($storeId);
    }

    /**
     * Test updateMBESettings function
     *
     * @return void
     */
    public function testUpdateMBESettingsWhenBusinessIdIsNUll(): void
    {
        $storeId = 9999;
        $accessToken = '[[]][[****|||TESTTOKEN';
        $businessId = 1212121212;
        $response = ['data' => []];

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($accessToken);
        
        $this->systemConfig
            ->method('getExternalBusinessId')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn(null);

        $this->graphApiAdapter
            ->method('getFBEInstalls')
            ->with(
                $this->equalTo($accessToken),
                $this->equalTo($businessId)
            )
            ->willReturn($response);

        $this->mbeIntallMockObj->updateMBESettings($storeId);
    }

    /**
     * Test updateMBESettings function
     *
     * @return void
     */
    public function testUpdateMBESettingsWhenAccessTokenIsNUll(): void
    {
        $storeId = 9999;
        $accessToken = '[[]][[****|||TESTTOKEN';
        $businessId = 1212121212;
        $response = ['data' => []];

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn(null);
        
        $this->systemConfig
            ->method('getExternalBusinessId')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($businessId);

        $this->graphApiAdapter
            ->method('getFBEInstalls')
            ->with(
                $this->equalTo($accessToken),
                $this->equalTo($businessId)
            )
            ->willReturn($response);

        $this->mbeIntallMockObj->updateMBESettings($storeId);
    }

    /**
     * Test deleteMBESettings function
     *
     * @return void
     */
    public function testDeleteMBESettings(): void
    {
        $storeId = 9999;
        $accessToken = '[[]][[****|||TESTTOKEN';
        $businessId = 1212121212;
        $response = ['data' => []];

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($accessToken);
        
        $this->systemConfig
            ->method('getExternalBusinessId')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($businessId);

        $this->graphApiAdapter
            ->method('deleteFBEInstalls')
            ->with(
                $this->equalTo($accessToken),
                $this->equalTo($businessId)
            )
            ->willReturn('');

        $this->mbeIntallMockObj->deleteMBESettings($storeId);
    }

    /**
     * Test repairCommercePartnerIntegration function
     *
     * @return void
     */
    public function testRepairCommercePartnerIntegration(): void
    {
        $storeId = 9999;
        $accessToken = '[[]][[****|||TESTTOKEN';
        $businessId = 1212121212;
        $response = ['data' => []];
        $customToken = 'customToken';
        $sellerPlatForm = 'AdobeCommerce';
        $moduleVersion = 'v1.4.3';
        $response = ['success' => true, 'id' => 1234];

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($accessToken);
        
        $this->systemConfig
            ->method('getExternalBusinessId')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($businessId);
        
        $this->apiKeyService
            ->method('getCustomApiKey')
            ->willReturn($customToken);
        
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getBaseUrl')->willReturn(UrlInterface::URL_TYPE_WEB);
        $this->storeManagerInterface
            ->method('getStore')
            ->with($this->equalTo($storeId))
            ->willReturn($storeMock);
        
        $this->adobeCloudConfig
            ->method('getCommercePartnerSellerPlatformType')
            ->willReturn($sellerPlatForm);

        $this->systemConfig
            ->method('getModuleVersion')
            ->willReturn($moduleVersion);

        $this->graphApiAdapter
            ->method('repairCommercePartnerIntegration')
            ->with(
                $this->equalTo($businessId),
                $this->equalTo(UrlInterface::URL_TYPE_WEB),
                $this->equalTo($customToken),
                $this->equalTo($accessToken),
                $this->equalTo($sellerPlatForm),
                $this->equalTo($moduleVersion)
            )
            ->willReturn($response);
        
        $this->systemConfig
            ->method('getCommercePartnerIntegrationId')
            ->willReturn($response['id']);

        $result = $this->mbeIntallMockObj->repairCommercePartnerIntegration($storeId);

        $this->assertTrue($result);
    }

    /**
     * Test repairCommercePartnerIntegration function
     *
     * @return void
     */
    public function testRepairCommercePartnerIntegrationWithFalseResponseFromGraphql(): void
    {
        $storeId = 9999;
        $accessToken = '[[]][[****|||TESTTOKEN';
        $businessId = 1212121212;
        $response = ['data' => []];
        $customToken = 'customToken';
        $sellerPlatForm = 'AdobeCommerce';
        $moduleVersion = 'v1.4.3';
        $response = ['success' => false];

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($accessToken);
        
        $this->systemConfig
            ->method('getExternalBusinessId')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($businessId);
        
        $this->apiKeyService
            ->method('getCustomApiKey')
            ->willReturn($customToken);
        
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getBaseUrl')->willReturn(UrlInterface::URL_TYPE_WEB);
        $this->storeManagerInterface
            ->method('getStore')
            ->with($this->equalTo($storeId))
            ->willReturn($storeMock);
        
        $this->adobeCloudConfig
            ->method('getCommercePartnerSellerPlatformType')
            ->willReturn($sellerPlatForm);

        $this->systemConfig
            ->method('getModuleVersion')
            ->willReturn($moduleVersion);

        $this->graphApiAdapter
            ->method('repairCommercePartnerIntegration')
            ->with(
                $this->equalTo($businessId),
                $this->equalTo(UrlInterface::URL_TYPE_WEB),
                $this->equalTo($customToken),
                $this->equalTo($accessToken),
                $this->equalTo($sellerPlatForm),
                $this->equalTo($moduleVersion)
            )
            ->willReturn($response);

        $result = $this->mbeIntallMockObj->repairCommercePartnerIntegration($storeId);

        $this->assertFalse($result);
    }
}
