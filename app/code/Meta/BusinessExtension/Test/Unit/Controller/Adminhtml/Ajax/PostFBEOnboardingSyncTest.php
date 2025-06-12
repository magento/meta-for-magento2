<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store as CoreStore;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\PostFBEOnboardingSync;

class PostFBEOnboardingSyncTest extends TestCase
{
    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->eventManager = $this->createMock(EventManager::class);

        $objectManager = new ObjectManager($this);
        $this->postFbeOnboardingSync = $objectManager->getObject(
            PostFBEOnboardingSync::class,
            [
                'context' => $this->context,
                'resultJsonFactory' => $this->resultJsonFactory,
                'fbeHelper' => $this->fbeHelper,
                'systemConfig' => $this->systemConfig,
                'eventManager' => $this->eventManager,
            ]
            );
    }

    /**
     * Test executeForJson function
     * 
     * @return void
     */
    public function testExecuteForJson(): void
    {
        $storeId = 1;
        $accessToken = 'rfGG$!@3434';

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('storeId')
            ->willReturn($storeId);
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getName')->willReturn('Magento_Default');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->systemConfig->expects($this->once())
            ->method('getStoreManager')
            ->willReturn($storeManager);
        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->with($storeId)
            ->willReturn($accessToken);

        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'facebook_fbe_onboarding_after',
                ['store_id' => $storeId]
            )
            ->willReturnSelf();

        $result = $this->postFbeOnboardingSync->executeForJson();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result);
    }

    /**
     * Test executeForJson function
     * 
     * @return void
     */
    public function testExecuteForJsonWithEmptyStoreId(): void
    {
        $storeId = null;

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('storeId')
            ->willReturn($storeId);
        
        $this->fbeHelper->expects($this->once())
            ->method('log')
            ->with('StoreId param is not set for Post FBE onboarding sync')
            ->willReturnSelf();

        $result = $this->postFbeOnboardingSync->executeForJson();

        $this->assertFalse($result['success']);
        $this->assertIsArray($result);
    }

    /**
     * Test executeForJson function
     * 
     * @return void
     */
    public function testExecuteForJsonWithEmptyAccessToken(): void
    {
        $storeId = 1;
        $accessToken = 0;

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('storeId')
            ->willReturn($storeId);
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getName')->willReturn('Magento_Default');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->systemConfig->expects($this->once())
            ->method('getStoreManager')
            ->willReturn($storeManager);
        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->with($storeId)
            ->willReturn($accessToken);
        
        $this->fbeHelper->expects($this->once())
            ->method('log')
            ->willReturnSelf();

        $result = $this->postFbeOnboardingSync->executeForJson();

        $this->assertFalse($result['success']);
        $this->assertIsArray($result);
    }

    /**
     * Test executeForJson function
     * 
     * @return void
     */
    public function testExecuteForJsonWithException(): void
    {
        $storeId = 1;
        $accessToken = 0;

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('storeId')
            ->willReturn($storeId);
        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('getName')->willReturn('Magento_Default');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->systemConfig->expects($this->once())
            ->method('getStoreManager')
            ->willReturn($storeManager);
        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->with($storeId)
            ->willThrowException(new \Exception("Error Occured"));
        
        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with(
                $this->isInstanceOf(\Exception::class),
                [
                    'store_id' => $storeId,
                    'event' => 'post_fbe_onboarding',
                    'event_type' => 'post_fbe_onboarding_sync'
                ]
            )
            ->willReturnSelf();

        $result = $this->postFbeOnboardingSync->executeForJson();

        $this->assertFalse($result['success']);
        $this->assertIsArray($result);
        $this->assertSame("Error Occured", $result['message']);
    }
}