<?php
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

use Magento\Security\Model\AdminSessionsManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\Fbdeleteasset;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\ResourceConnection;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class FbdeleteassetTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $fbeHelper;

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
        $resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $sessionManager = $this->createMock(AdminSessionsManager::class);
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->fbdeleteasset = new Fbdeleteasset(
            $resultJsonFactory,
            $this->fbeHelper,
            $sessionManager,
            $resourceConnection,
            $this->systemConfig,
            $this->request
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

        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(Fbdeleteasset::DELETE_SUCCESS_MESSAGE, $result['message']);
    }
}
