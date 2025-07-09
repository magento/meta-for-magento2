<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use Meta\BusinessExtension\Controller\Adminhtml\Ajax\FbeInstallsSave;
use Meta\BusinessExtension\Helper\FBEHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Model\MBEInstalls;
use Magento\Framework\Exception\LocalizedException;

class FbeInstallsSaveTest extends TestCase
{
    /**
     * Setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->mbeInstalls = $this->createMock(MBEInstalls::class);

        $objectManager = new ObjectManager($this);
        $this->fbeInstallsSaveMockObj = $objectManager->getObject(
            FbeInstallsSave::class,
            [
                'request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'fbeHelper' => $this->fbeHelper,
                'saveFBEInstallsResponse' => $this->mbeInstalls
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
        $response = ["success" => false];
        $json = $this->createMock(Json::class);
        $json->expects($this->once())
            ->method('setData')
            ->willReturn($response);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['data'], ['storeId'])
            ->willReturnCallback(function ($param) {
                if ($param === 'data') {
                    return [];
                }
                if ($param === 'storeId') {
                    return 1;
                }
                $this->fail("Unexpected call to getParam with parameter: {$paramName}");
            });

        $this->mbeInstalls->expects($this->once())
            ->method('save')
            ->willReturn(false);
        
        $result = $this->fbeInstallsSaveMockObj->execute();
        $this->assertEquals($response, $result);
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithEmptyStore(): void
    {
        $response = [
            'success' => false,
            'message' => 'There was an issue saving FbeInstalls config.'
        ];
        $json = $this->createMock(Json::class);
        $json->expects($this->once())
            ->method('setData')
            ->willReturn($response);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['data'], ['storeId'])
            ->willReturnCallback(function ($param) {
                if ($param === 'data') {
                    return [];
                }
                if ($param === 'storeId') {
                    return "";
                }
                $this->fail("Unexpected call to getParam with parameter: {$paramName}");
            });
        
        $result = $this->fbeInstallsSaveMockObj->execute();
        $this->assertEquals('There was an issue saving FbeInstalls config.', $result['message']);
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $response = ["success" => false];
        $json = $this->createMock(Json::class);
        $json->expects($this->once())
            ->method('setData')
            ->willThrowException(new \Exception("Error Occured"));
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->request->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(['data'], ['storeId'])
            ->willReturnCallback(function ($param) {
                if ($param === 'data') {
                    return [];
                }
                if ($param === 'storeId') {
                    return 1;
                }
                $this->fail("Unexpected call to getParam with parameter: {$paramName}");
            });

        $this->mbeInstalls->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception("Error occured"));
        
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The was an error while saving FbeInstalls config. Please contact admin for more details.');
        
        $result = $this->fbeInstallsSaveMockObj->execute();
    }
}