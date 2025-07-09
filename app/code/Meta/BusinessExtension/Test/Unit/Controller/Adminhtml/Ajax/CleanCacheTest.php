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
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\CleanCache;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config;
use PHPUnit\Framework\TestCase;
use Magento\Store\Model\Store as CoreStore;
use Magento\Framework\Phrase;

class CleanCacheTest extends TestCase
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
     * @var Context
     */
    private Context $context;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var CleanCache
     */
    private CleanCache $controller;

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
        $this->context = $this->createMock(Context::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(Config::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->controller = new CleanCache(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper,
            $this->systemConfig
        );
    }

    /**
     * Test execute for json method
     *
     * @return void
     */
    public function testExecuteForJson(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('cleanCache');

        $result = $this->controller->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Config cache successfully cleaned', $result['message']);
    }

    /**
     * Test execute for json method
     *
     * @return void
     */
    public function testExecuteForJsonIsDebugMode(): void
    {
        $this->systemConfig->expects($this->once())
            ->method('cleanCache');
        $this->systemConfig->expects($this->once())
            ->method('isDebugMode')
            ->willReturn(true);
        $this->fbeHelper->expects($this->once())
            ->method('log')
            ->with((new Phrase('Config cache successfully cleaned'))->render())
            ->willReturnSelf();
        $result = $this->controller->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Config cache successfully cleaned', $result['message']);
    }

    /**
     * Test execute for json method
     *
     * @return void
     */
    public function testExecuteForJsonForException(): void
    {
        $errorMessage = 'Failed to clean cache due to permission issues.';
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Website 1');
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('admin');

        $this->systemConfig->expects($this->once())
            ->method('cleanCache')
            ->willThrowException(new \Exception($errorMessage));

        $this->fbeHelper->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with(
                $this->isInstanceOf(\Exception::class),
                [
                    'store_id' => 1,
                    'event' => 'clean_cache',
                    'event_type' => 'manual_clean'
                ]
            );

        $result = $this->controller->executeForJson();
        $this->assertIsArray($result);
        $this->assertEquals($errorMessage, $result['message']);
    }
}
