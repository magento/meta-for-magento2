<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use Meta\BusinessExtension\Controller\Adminhtml\Ajax\MbeUpdateInstalledConfig;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\MBEInstalls;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface;

class MbeUpdateInstalledConfigTest extends TestCase
{
    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->mbeInstalls = $this->createMock(MBEInstalls::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->eventManager = $this->createMock(EventManager::class);
        
        $objectManager = new ObjectManager($this);
        $this->mbeUpdateInstalledConfigMockObj = $objectManager->getObject(
            MbeUpdateInstalledConfig::class,
            [
                'context' => $this->context,
                'resultJsonFactory' => $this->resultJsonFactory,
                'fbeHelper' => $this->fbeHelper,
                'mbeInstalls' => $this->mbeInstalls,
                'logger' => $this->logger,
                'request' => $this->request,
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
        $triggerPostOnboarding = 'true';

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['storeId', null, $storeId],
                ['triggerPostOnboarding', null, $triggerPostOnboarding],
            ]);
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnSelf();
        $this->mbeInstalls->expects($this->once())
            ->method('updateMBESettings')
            ->with($storeId)
            ->willReturnSelf();
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'facebook_fbe_onboarding_after',
                ['store_id' => $storeId]
            )
            ->willReturnSelf();
        $result = $this->mbeUpdateInstalledConfigMockObj->executeForJson();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    /**
     * Test executeForJson function
     *
     * @return void
     */
    public function testExecuteForJsonWithNullStoreId(): void
    {
        $storeId = 1;
        $triggerPostOnboarding = 'true';

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['storeId', null, null],
                ['triggerPostOnboarding', null, $triggerPostOnboarding],
            ]);
        $result = $this->mbeUpdateInstalledConfigMockObj->executeForJson();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
    }

    /**
     * Test executeForJson function
     *
     * @return void
     */
    public function testExecuteForJsonWithException(): void
    {
        $storeId = 1;
        $triggerPostOnboarding = 'true';

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['storeId', null, $storeId],
                ['triggerPostOnboarding', null, $triggerPostOnboarding],
            ]);
        $this->logger->expects($this->exactly(1))
            ->method('info')
            ->willReturnSelf();
        $this->mbeInstalls->expects($this->once())
            ->method('updateMBESettings')
            ->with($storeId)
            ->willThrowException(new \Exception("Exception Occured"));
        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with(
                $this->isInstanceOf(\Exception::class),
                [
                    'store_id' => $storeId,
                    'event' => 'update_mbe_config',
                    'event_type' => 'update_mbe_config'
                ]
            );
        $result = $this->mbeUpdateInstalledConfigMockObj->executeForJson();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
    }
}
