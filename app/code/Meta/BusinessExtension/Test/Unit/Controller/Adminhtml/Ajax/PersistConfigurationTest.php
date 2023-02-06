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

use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use PHPUnit\Framework\MockObject\MockObject;

class PersistConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    protected $fbeHelper;

    /**
     * @var MockObject
     */
    protected $systemConfig;

    /**
     * @var MockObject
     */
    protected $context;

    /**
     * @var MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var \Meta\BusinessExtension\Controller\Adminhtml\Ajax\PersistConfiguration
     */
    protected $fbFeedPush;

    /**
     * @var MockObject
     */
    protected $request;

    /**
     * @var MockObject
     */
    protected $graphApiAdapter;

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
        $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->resultJsonFactory = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->fbeHelper = $this->createMock(\Meta\BusinessExtension\Helper\FBEHelper::class);
        $this->systemConfig = $this->createMock(\Meta\BusinessExtension\Model\System\Config::class);
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->graphApiAdapter = $this->createMock(GraphAPIAdapter::class);

        $this->fbFeedPush = new \Meta\BusinessExtension\Controller\Adminhtml\Ajax\PersistConfiguration(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper,
            $this->systemConfig,
            $this->graphApiAdapter
        );
    }

    /**
     * @return void
     */
    public function testExecuteForJson()
    {
        $storeId = 1;
        $merchantSettingsId = 15964;
        $pageAccessToken = 'EAACxonUmtyIBABauSDrrahBhBg7D2QwZDZD';
        $this->request->method('getParam')->willReturn($storeId);
        $externalBusinessId = 'fbe_magento_1_63c34a23324';
        $this->request->method('getParam')->willReturn($externalBusinessId);
        $this->systemConfig->method('saveConfig')->willReturn($this->systemConfig);
        $this->graphApiAdapter->method('getPageAccessToken')->willReturn($pageAccessToken);
        $this->graphApiAdapter->method('getPageMerchantSettingsId')->willReturn($merchantSettingsId);

        $result = $this->fbFeedPush->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
    }
}
