<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\AbstractAjax;
use PHPUnit\Framework\TestCase;

class AbstractAjaxTest extends TestCase
{
    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactory;

    /**
     * @var FBEHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fbeHelper;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var AbstractAjax|\PHPUnit\Framework\MockObject\MockObject
     */
    private $abstractAjax;

    /**
     * @var AdminSessionsManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $adminSessionsManager;

    /**
     * @var AdminSessionInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $adminSession;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->adminSessionsManager = $this->createMock(AdminSessionsManager::class);
        $this->adminSession = $this->getMockBuilder(AdminSessionInfo::class)
            ->addMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->abstractAjax = $this->getMockForAbstractClass(
            AbstractAjax::class,
            [
                $this->context,
                $this->resultJsonFactory,
                $this->fbeHelper
            ]
        );

        $this->fbeHelper->method('createObject')->willReturn($this->adminSessionsManager);
        
        $reflection = new \ReflectionClass(AbstractAjax::class);
        $configModulesProperty = $reflection->getProperty('fbeHelper');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->abstractAjax, $this->fbeHelper);
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWhenAdminIsNotLoggedIn(): void
    {
        $this->adminSessionsManager->method('getCurrentSession')->willReturn($this->adminSession);
        $this->adminSession->method('getStatus')->willReturn(0); // Not logged in

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('This endpoint is for logged in admin and ajax only.');
        $this->abstractAjax->execute();
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWhenAdminIsLoggedInAndExecutionIsSuccessful(): void
    {
        $this->adminSessionsManager->method('getCurrentSession')->willReturn($this->adminSession);
        $this->adminSession->method('getStatus')->willReturn(1);

        $expectedJsonData = ['success' => true, 'data' => 'test'];
        $this->abstractAjax->method('executeForJson')->willReturn($expectedJsonData);

        $jsonResult = $this->createMock(Json::class);
        $this->resultJsonFactory->method('create')->willReturn($jsonResult);
        $jsonResult->expects($this->once())
            ->method('setData')
            ->with($expectedJsonData)
            ->willReturnSelf();

        $result = $this->abstractAjax->execute();

        $this->assertSame($jsonResult, $result);
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWhenAdminIsLoggedInAndExecutionThrowsException(): void
    {
        $this->adminSessionsManager->method('getCurrentSession')->willReturn($this->adminSession);
        $this->adminSession->method('getStatus')->willReturn(1);

        $exceptionMessage = 'Something went wrong during JSON execution.';
        $this->abstractAjax->method('executeForJson')->willThrowException(new Exception($exceptionMessage));

        $this->fbeHelper->expects($this->once())
            ->method('logCritical')
            ->with($exceptionMessage);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('There was error while processing your request. Please contact admin for more details.');

        $this->abstractAjax->execute();
    }
}