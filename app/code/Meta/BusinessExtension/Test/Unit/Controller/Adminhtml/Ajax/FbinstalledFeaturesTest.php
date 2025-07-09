<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\FbinstalledFeatures;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Meta\BusinessExtension\Helper\FBEHelper;

class FbinstalledFeaturesTest extends TestCase
{
    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->fbInstalledFeatureResource = $this->createMock(FacebookInstalledFeature::class);

        $objectManager = new ObjectManager($this);
        $this->fbinstalledFeaturesMockObj = $objectManager->getObject(
            FbinstalledFeatures::class,
            [
                'request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'fbeHelper' => $this->fbeHelper,
                'fbInstalledFeatureResource' => $this->fbInstalledFeatureResource
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
        $response = ['success' => true];

        $json = $this->createMock(Json::class);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['storeId'], ['installed_features'])
            ->willReturnCallback(function ($param) {
                if ($param === 'storeId') {
                    return 1;
                }
                if ($param === 'installed_features') {
                    return '{"is_installed":true}';
                }
                $this->fail("Unexpected call to getParam with parameter: {$paramName}");
            });

        $this->fbInstalledFeatureResource->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);
        $this->fbInstalledFeatureResource->expects($this->once())
            ->method('saveResponseData')
            ->willReturn(true);
        $json->expects($this->once())
            ->method('setData')
            ->willReturn($response);

        $result = $this->fbinstalledFeaturesMockObj->execute();

        $this->assertEquals($response, $result);
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithWrongInstalledData(): void
    {
        $storeId = 1;
        $response = ['success' => false];

        $json = $this->createMock(Json::class);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['storeId'], ['installed_features'])
            ->willReturnCallback(function ($param) {
                if ($param === 'installed_features') {
                    return [];
                }
                if ($param === 'storeId') {
                    return 1;
                }
                $this->fail("Unexpected call to getParam with parameter: {$paramName}");
            });
        $json->expects($this->once())
            ->method('setData')
            ->willReturn($response);

        $result = $this->fbinstalledFeaturesMockObj->execute();

        $this->assertEquals($response, $result);
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $storeId = 1;
        $response = ['success' => false];

        $json = $this->createMock(Json::class);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->request->expects($this->exactly(3))
            ->method('getParam')
            ->willReturn($storeId);
        

        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with(
                $this->isInstanceOf(\Exception::class),
                [
                    'store_id' => $storeId,
                    'event' => 'fbe_installs',
                    'event_type' => 'save_installed_features'
                ]
                );
        $json->expects($this->once())
            ->method('setData')
            ->willThrowException(new \Exception("Exception Occured"));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('There was error while processing your request. Please contact admin for more details.');

        $result = $this->fbinstalledFeaturesMockObj->execute();
    }
}