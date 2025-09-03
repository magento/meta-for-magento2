<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use CURLFile;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\View\FileFactory;
use Meta\BusinessExtension\Helper\GraphAPIConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Http\Message\StreamInterface;

class GraphAPIAdapterTest extends TestCase
{
    private $userToken = 'meta_user';
    /**
     * Class setUp function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->curlFactory = $this->createMock(CurlFactory::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->graphAPIConfig = $this->createMock(GraphAPIConfig::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->responseInterface = $this->createMock(ResponseInterface::class);

        $objectManager = new ObjectManager($this);
        $this->graphApiAdapterMockObj = $objectManager->getObject(
            GraphAPIAdapter::class,
            [
                'systemConfig' => $this->systemConfig,
                'logger' => $this->logger,
                'curlFactory' => $this->curlFactory,
                'fileFactory' => $this->fileFactory,
                'graphAPIConfig' => $this->graphAPIConfig,
                'scopeConfig' => $this->scopeConfig
            ]
        );

        $this->client = $this->createMock(
            Client::class,
            [
                'base_uri' => "https://business.facebook.com/v20.2/",
                'timeout' => 60,
            ]
        );
    }

    /**
     * Test setAccessToken function
     *
     * @return void
     */
    public function testSetAccessToken(): void
    {
        $accessToken = 'wsxz@#!.,';
        $functionReturn = $this->graphApiAdapterMockObj->setAccessToken($accessToken);
        $this->assertInstanceOf(GraphAPIAdapter::class, $functionReturn);
    }

    /**
     * Test setDebugMode function
     *
     * @return void
     */
    public function testSetDebugMode(): void
    {
        $functionReturn = $this->graphApiAdapterMockObj->setDebugMode(true);
        $this->assertInstanceOf(GraphAPIAdapter::class, $functionReturn);
    }

    /**
     * Test getPageTokenFromUserToken function
     *
     * @return void
     */
    public function testGetPageTokenFromUserToken(): void
    {
        $curlResponse['data'][0]['access_token'] = $this->userToken;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getPageTokenFromUserToken($this->userToken);
        $this->assertSame($this->userToken, $functionReturn);
    }

    /**
     * Test getPageTokenFromUserToken function
     *
     * @return void
     */
    public function testGetPageTokenFromUserTokenValidateNullResponse(): void
    {
        $curlResponse = null;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getPageTokenFromUserToken($this->userToken);
        $this->assertFalse($functionReturn);
    }

    /**
     * Test getPageIdFromUserToken function
     *
     * @return void
     */
    public function testGetPageIdFromUserTokenWithNull(): void
    {
        $curlResponse = null;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getPageIdFromUserToken($this->userToken);
        $this->assertFalse($functionReturn);
    }

    /**
     * Test getPageIdFromUserToken function
     *
     * @return void
     */
    public function testGetPageIdFromUserToken(): void
    {
        $curlResponse['data'][0]['id'] = $this->userToken;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getPageIdFromUserToken($this->userToken);
        $this->assertSame($this->userToken, $functionReturn);
    }

    /**
     * Test getPageAccessToken function
     *
     * @return void
     */
    public function testGetPageAccessToken(): void
    {
        $accessToken = 'qws2@#*.,_';
        $pageId = 123;
        $curlResponse['access_token'] = $accessToken;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getPageAccessToken($accessToken, $pageId);
        $this->assertSame($accessToken, $functionReturn);
    }

    /**
     * Test getPageAccessToken function
     *
     * @return void
     */
    public function testGetPageAccessTokenWithNullResponse(): void
    {
        $accessToken = 'qws2@#*.,_';
        $pageId = 123;
        $curlResponse['access_token'] = null;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getPageAccessToken($accessToken, $pageId);
        $this->assertFalse($functionReturn);
    }

    /**
     * Test getCommerceExtensionIFrameURL function
     *
     * @return void
     */
    public function testGetCommerceExtensionIFrameURL(): void
    {
        $accessToken = 'qws2@#*.,_';
        $externalBusinessId = '123';
        $curlResponse['commerce_extension']['uri'] = 'https://www.commercepartnerhub.com/';

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'facebook/internal/extension_base_url',
                ScopeInterface::SCOPE_STORE
            )->willReturn('https://business.facebook.com/');

        $functionReturn = $this->graphApiAdapterMockObj->getCommerceExtensionIFrameURL($externalBusinessId, $accessToken);
        $this->assertSame('https://business.facebook.com/', $functionReturn);
    }

    /**
     * Test getCommerceExtensionIFrameURL function
     *
     * @return void
     */
    public function testGetCommerceExtensionIFrameURLWithoutAdminConfiguredUrl(): void
    {
        $accessToken = 'qws2@#*.,_';
        $externalBusinessId = '123';
        $curlResponse['commerce_extension']['uri'] = 'https://www.commercepartnerhub.com/';

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);

        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                'facebook/internal/extension_base_url',
                ScopeInterface::SCOPE_STORE
            )->willReturn('');

        $functionReturn = $this->graphApiAdapterMockObj->getCommerceExtensionIFrameURL($externalBusinessId, $accessToken);
        $this->assertSame($curlResponse['commerce_extension']['uri'], $functionReturn);
    }

    /**
     * Test getCommerceAccountData function
     *
     * @return void
     */
    public function testGetCommerceAccountData(): void
    {
        $accessToken = 'qws2@#*.,_';
        $commerceAccountId = '123';
        $curlResponse['merchant_page']['id'] = '123';
        $curlResponse['product_catalogs']['data'][0]['id'] = '1234';

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $expectedResult = [
            'page_id' => $curlResponse['merchant_page']['id'],
            'catalog_id' => $curlResponse['product_catalogs']['data'][0]['id']
        ];
        $functionReturn = $this->graphApiAdapterMockObj->getCommerceAccountData($commerceAccountId, $accessToken);
        $this->assertSame($expectedResult, $functionReturn);
    }

    /**
     * Test associateMerchantSettingsWithApp function
     *
     * @return void
     */
    public function testAssociateMerchantSettingsWithApp(): void
    {
        $accessToken = 'qws2@#*.,_';
        $commerceAccountId = '123';
        $curlResponse['merchant_page']['id'] = $commerceAccountId;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->associateMerchantSettingsWithApp($commerceAccountId, $accessToken);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getCatalogFeeds function
     *
     * @return void
     */
    public function testGetCatalogFeeds(): void
    {
        $catalogId = '123';
        $curlResponse['data'] = $catalogId;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getCatalogFeeds($catalogId);
        $this->assertSame($curlResponse['data'], $functionReturn);
    }

    /**
     * Test getFeed function
     *
     * @return void
     */
    public function testGetFeed(): void
    {
        $feedId = '123';
        $curlResponse['data'] = $feedId;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getFeed($feedId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test createEmptyFeed function
     *
     * @return void
     */
    public function testCreateEmptyFeed(): void
    {
        $catalogId = '123';
        $name = 'feed_1';
        $isPromotion = false;
        $curlResponse['id'] = $catalogId;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->createEmptyFeed($catalogId, $name, $isPromotion);
        $this->assertSame($curlResponse['id'], $functionReturn);
    }

    /**
     * Test catalogBatchRequest function
     *
     * @return void
     */
    public function testCatalogBatchRequest(): void
    {
        $catalogId = '123';
        $requests = ['feed_1' => 1, 'feed_2' => 2];
        $curlResponse['id'] = $catalogId;

        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->catalogBatchRequest($catalogId, $requests);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test graphAPIBatchRequest function
     *
     * @return void
     */
    public function testGraphAPIBatchRequest(): void
    {
        $requests = ['feed_1' => 1, 'feed_2' => 2];
        $curlResponse = $requests;
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->graphAPIBatchRequest($requests);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getOrderDetails function
     *
     * @return void
     */
    public function testGetOrderDetails(): void
    {
        $orderId = '40001';
        $curlResponse = ['order_status' => 'processing', 'order_id' => $orderId];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getOrderDetails($orderId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getOrders function
     *
     * @return void
     */
    public function testGetOrders(): void
    {
        $orderId = '40001';
        $curlResponse = ['order_status' => 'processing', 'order_id' => $orderId];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getOrders($orderId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getRefunds function
     *
     * @return void
     */
    public function testGetRefunds(): void
    {
        $orderId = '40001';
        $curlResponse['data'] = ['order_status' => 'processing', 'order_id' => $orderId];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getRefunds($orderId);
        $this->assertSame($curlResponse['data'], $functionReturn);
    }

    /**
     * Test getCancellations function
     *
     * @return void
     */
    public function testGetCancellations(): void
    {
        $orderId = '40001';
        $curlResponse['data'] = ['order_status' => 'processing', 'order_id' => $orderId];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getCancellations($orderId);
        $this->assertSame($curlResponse['data'], $functionReturn);
    }

    /**
     * Test getOrderItems function
     *
     * @return void
     */
    public function testGetOrderItems(): void
    {
        $orderId = '40001';
        $curlResponse = ['order_status' => 'processing', 'order_id' => $orderId];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getOrderItems($orderId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getOrderItems function
     *
     * @return void
     */
    public function testAcknowledgeOrders(): void
    {
        $orderId = '40001';
        $orderIds = ['12345', '43567', '09871'];
        $curlResponse = ['order_status' => 'processing', 'order_id' => $orderId];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->acknowledgeOrders($orderId, $orderIds);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getProductInfo function
     *
     * @return void
     */
    public function testGetProductInfo(): void
    {
        $productId = '40001';
        $curlResponse = ['price' => '30', 'sale_price' => '25'];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getProductInfo($productId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getProductByRetailerId function
     *
     * @return void
     */
    public function testGetProductByRetailerId(): void
    {
        $retailerId = '40001';
        $catalogId = '123';
        $curlResponse = ['price' => '30', 'sale_price' => '25'];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getProductByRetailerId($catalogId, $retailerId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getProductsByFacebookProductIds function
     *
     * @return void
     */
    public function testGetProductsByFacebookProductIds(): void
    {
        $fbProductIds = ['40001', '40002', '40003', '40004'];
        $catalogId = '123';
        $curlResponse = ['price' => '30', 'sale_price' => '25'];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getProductsByFacebookProductIds($catalogId, $fbProductIds);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getProductErrors function
     *
     * @return void
     */
    public function testGetProductErrors(): void
    {
        $fbProductId = '40001';
        $curlResponse = ['price' => '30', 'sale_price' => '25'];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getProductErrors($fbProductId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getCatalogDiagnostics function
     *
     * @return void
     */
    public function testGetCatalogDiagnostics(): void
    {
        $catalogId = '40001';
        $curlResponse = ['price' => '30', 'sale_price' => '25'];
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getCatalogDiagnostics($catalogId);
        $this->assertSame($curlResponse, $functionReturn);
    }

    /**
     * Test getFBEInstalls function
     *
     * @return void
     */
    public function testGetFBEInstalls(): void
    {
        $accessToken = 'yu$%#op0o';
        $externalBusinessId = '456';
        $curlResponse = true;
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->getFBEInstalls($accessToken, $externalBusinessId);
        $this->assertTrue($functionReturn);
    }

    /**
     * Test deleteFBEInstalls function
     *
     * @return void
     */
    public function testDeleteFBEInstalls(): void
    {
        $accessToken = 'yu$%#op0o';
        $externalBusinessId = '456';
        $curlResponse = true;
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->deleteFBEInstalls($accessToken, $externalBusinessId);
        $this->assertTrue($functionReturn);
    }

    /**
     * Test persistLogToMeta function
     *
     * @return void
     */
    public function testPersistLogToMeta(): void
    {
        $accessToken = 'yu$%#op0o';
        $request = [
            'access_token' => $accessToken,
            'event' => 'fb_event',
            'event_type' => 'fb_event',
            'commerce_merchant_settings_id' => 'new_key',
            'exception_message' => 'meta installation path not found',
            'exception_trace' => null,
            'exception_code' => null,
            'exception_class' => null,
            'catalog_id' => 123,
            'order_id' => 4001,
            'promotion_id' => null,
            'external_business_id' => 123,
            'commerce_partner_integration_id' => 3456,
            'page_id' => 1098,
            'pixel_id' => 'jk123',
            'flow_name' => null,
            'flow_step' => null,
            'incoming_params' => null,
            'seller_platform_app_version' => 'v4.5.0',
            'extra_data' => [],
        ];
        
        $curlResponse = $request;
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->persistLogToMeta($request, $accessToken);
        $this->assertSame($request, $functionReturn);
    }

    /**
     * Test getGraphApiVersion function
     *
     * @return void
     */
    public function testGetGraphApiVersion(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('getGraphAPIVersion')
            ->willReturn('v20.2');
        $functionReturn = $this->graphApiAdapterMockObj->getGraphApiVersion();
        $this->assertSame('v20.2', $functionReturn);
    }

    /**
     * Test getGraphApiVersion function
     *
     * @return void
     */
    public function testGetGraphApiVersionWithEmpty(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('getGraphAPIVersion')
            ->willReturn('');
        $functionReturn = $this->graphApiAdapterMockObj->getGraphApiVersion();
        $this->assertEmpty($functionReturn);
    }

    /**
     * Test repairCommercePartnerIntegration function
     *
     * @return void
     */
    public function testRepairCommercePartnerIntegration(): void
    {
        $externalBusinessId = '1223';
        $shopDomain = 'www.test.com';
        $customToken = 'dfV%$#12P_jk';
        $accessToken = 'dfV%$#12P_jk';
        $sellerPlatformType = 'adobe_commerce';
        $extensionVersion = 'v4.5.0';

        $request = [
            'access_token' => $accessToken,
            'fbe_external_business_id' => $externalBusinessId,
            'custom_token' => $customToken,
            'shop_domain' => $shopDomain,
            'commerce_partner_seller_platform_type' => $sellerPlatformType,
            'extension_version' => $extensionVersion
        ];
        
        $curlResponse = $request;
        $mockResponseBody = $this->createMock(StreamInterface::class);
        $mockResponseBody->method('__toString')->willReturn(json_encode($curlResponse));

        $this->responseInterface->method('getBody')->willReturn($mockResponseBody);
        
        $this->client->expects($this->atMost(1))
            ->method('request')
            ->willReturn($this->responseInterface);
        $reflection = new \ReflectionClass(GraphAPIAdapter::class);
        $configModulesProperty = $reflection->getProperty('client');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->graphApiAdapterMockObj, $this->client);

        $functionReturn = $this->graphApiAdapterMockObj->repairCommercePartnerIntegration(
            $externalBusinessId,
            $shopDomain,
            $customToken,
            $accessToken,
            $sellerPlatformType,
            $extensionVersion
        );
        $this->assertSame($request, $functionReturn);
    }
}
