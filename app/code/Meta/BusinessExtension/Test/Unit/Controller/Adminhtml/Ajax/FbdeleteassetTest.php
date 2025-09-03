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

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\Fbdeleteasset;
use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Magento\Framework\Phrase;

class FbdeleteassetTest extends TestCase
{
    /**
     * @var Fbdeleteasset
     */
    private $fbdeleteasset;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var FacebookInstalledFeature
     */
    private FacebookInstalledFeature $fbeInstalledFeatureResource;

    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->fbeInstalledFeatureResource = $this->createMock(FacebookInstalledFeature::class);
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $eventManager = $this->createMock(EventManager::class);
        $this->mbeInstalls = $this->createMock(\Meta\BusinessExtension\Model\MBEInstalls::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->fbdeleteasset = new Fbdeleteasset(
            $context,
            $resultJsonFactory,
            $this->fbeHelper,
            $this->systemConfig,
            $this->request,
            $this->fbeInstalledFeatureResource,
            $eventManager,
            $this->mbeInstalls,
            $this->logger
        );
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJson()
    {
        $storeId = 2;
        $this->request->method('getParam')->willReturn($storeId);
        $this->systemConfig->expects($this->atLeastOnce())
            ->method('deleteConfig')->willReturnSelf();
        $this->fbeInstalledFeatureResource->expects($this->atLeastOnce())
            ->method('deleteAll');

        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(Fbdeleteasset::DELETE_SUCCESS_MESSAGE, $result['message']);
    }

    /**
     * Test executeForJson function
     *
     * @return void
     */
    public function testExecuteForJsonWithNullStoreId(): void
    {
        $storeId = 2;
        $this->request->method('getParam')->willReturn(null);

        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals((new Phrase('There was a problem deleting the connection.
        Please try again.')), $result['error_message']);
    }

    /**
     * Test executeForJson function
     *
     * @return void
     */
    public function testExecuteForJsonThrowsDeleteMBESettingsException(): void
    {
        $storeId = 2;
        $this->request->method('getParam')->willReturn($storeId);
        $this->systemConfig->expects($this->atLeastOnce())
            ->method('deleteConfig')->willReturnSelf();
        $this->fbeInstalledFeatureResource->expects($this->atLeastOnce())
            ->method('deleteAll');
        $this->mbeInstalls->expects($this->once())
            ->method('deleteMBESettings')
            ->with($storeId)
            ->willThrowException(new \Exception("Exception occured"));
        $this->logger->expects($this->once())
            ->method('warning')
            ->with("Failed to delete MBE installation for ". $storeId .". The installation may not exist")
            ->willReturnSelf();
        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(Fbdeleteasset::DELETE_SUCCESS_MESSAGE, $result['message']);
    }

    /**
     * Test executeForJson function
     *
     * @return void
     */
    public function testExecuteForJsonThrowsException(): void
    {
        $storeId = 2;
        $this->request->method('getParam')->willReturn($storeId);
        $this->systemConfig->expects($this->atLeastOnce())
            ->method('deleteConfig')->willThrowException(new \Exception("Exception Occured"));
        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with(
                $this->isInstanceOf(\Exception::class),
                [
                    'store_id' => $storeId,
                    'event' => 'delete_connection',
                    'event_type' => 'manual_deletion'
                ]
            );
        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals("There was a problem deleting the connection. Please try again.", $result['error_message']);
    }
}
