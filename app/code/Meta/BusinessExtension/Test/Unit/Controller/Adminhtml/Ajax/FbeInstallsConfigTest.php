<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\FbeInstallsConfig;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class FbeInstallsConfigTest extends TestCase
{
    /**
     * Class setUp function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->graphApiAdapter = $this->createMock(GraphAPIAdapter::class);

        $objectManager = new ObjectManager($this);
        $this->fbeInstallsConfigMockObj = $objectManager->getObject(
            FbeInstallsConfig::class,
            [
                'request' => $this->request,
                'fbeHelper' => $this->fbeHelper,
                'jsonFactory' => $this->jsonFactory,
                'systemConfig' => $this->systemConfig,
                'graphAPIAdapter' => $this->graphApiAdapter
            ]
        );
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecute(): void
    {
        $storeId = 1;
        $externalBusinessId = 'we#$66^^ytu';
        $accessToken = 'access&&^^!343Token';
        $response = [
            'endpoint' => "https://facebook.com/v22/fbe_business/fbe_installs",
            'externalBusinessId' => $externalBusinessId,
            'accessToken' => $accessToken
        ];

        $json = $this->createMock(Json::class);
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);
        $json->expects($this->once())
            ->method('setData')
            ->willReturn($response);

        $this->graphApiAdapter->expects($this->once())
            ->method('getGraphApiVersion')
            ->willReturn('v22');

        $this->fbeHelper->expects($this->once())
            ->method('getGraphBaseURL')
            ->willReturn('https://facebook.coom/');

        $this->request->method('getParam')
            ->willReturn($storeId);

        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->willReturn($externalBusinessId);
        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $result = $this->fbeInstallsConfigMockObj->execute();

        $this->assertEquals($response, $result);
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecuteWithEmptyEndPoint(): void
    {
        $storeId = 1;
        $externalBusinessId = 'we#$66^^ytu';
        $accessToken = 'access&&^^!343Token';
        $response = [
            'endpoint' => "https://facebook.com/v22/fbe_business/fbe_installs",
            'externalBusinessId' => $externalBusinessId,
            'accessToken' => $accessToken
        ];

        $json = $this->createMock(Json::class);
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->graphApiAdapter->expects($this->once())
            ->method('getGraphApiVersion')
            ->willReturn('');

        $this->expectException(\TypeError::class);
        $result = $this->fbeInstallsConfigMockObj->execute();
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecuteWillThrowException(): void
    {
        $storeId = 1;
        $externalBusinessId = 'we#$66^^ytu';
        $accessToken = 'access&&^^!343Token';
        $response = [
            'endpoint' => "https://facebook.com/v22/fbe_business/fbe_installs",
            'externalBusinessId' => $externalBusinessId,
            'accessToken' => $accessToken
        ];

        $json = $this->createMock(Json::class);
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);
        $json->expects($this->once())
            ->method('setData')
            ->willThrowException(new \Exception('Error occured'));

        $this->graphApiAdapter->expects($this->once())
            ->method('getGraphApiVersion')
            ->willReturn('v22');

        $this->fbeHelper->expects($this->once())
            ->method('getGraphBaseURL')
            ->willReturn('https://facebook.coom/');

        $this->request->method('getParam')
            ->willReturn($storeId);

        $this->systemConfig->expects($this->once())
            ->method('getExternalBusinessId')
            ->willReturn($externalBusinessId);
        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with(
                $this->isInstanceOf(\Exception::class),
                [
                    'store_id' => $storeId,
                    'event' => 'fbe_installs',
                    'event_type' => 'get_config'
                ]
            );


        $this->fbeInstallsConfigMockObj->execute();
    }
}
