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

class PersistConfigurationTest extends \PHPUnit\Framework\TestCase
{
    protected $fbeHelper;

    protected $systemConfig;

    protected $context;

    protected $resultJsonFactory;

    protected $fbFeedPush;

    protected $request;

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
     * Test the case when external biz id already saved
     *
     * @return void
     */
    public function testExternalBizIdExists()
    {
        // @todo Temporarily disabling FB feed push in this version (see https://fburl.com/707tgrel)
        $this->markTestSkipped('must be revisited');

        $this->fbeHelper->method('getConfigValue')->willReturn('bizID');
        $result = $this->fbFeedPush->executeForJson();
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['message']);
        $this->assertEquals('One time feed push is completed at the time of setup', $result['message']);
    }

    /**
     * Test the case when external biz id already saved
     *
     * @return void
     */
    public function testExternalBizIdNotExists()
    {
        // @todo Temporarily disabling FB feed push in this version (see https://fburl.com/707tgrel)
        $this->markTestSkipped('must be revisited');

        $this->fbeHelper->method('getConfigValue')->willReturn(null);
        $this->request->method('getParam')->willReturn('randomStr');
        $this->fbeHelper->method('saveConfig')->willReturn(null);
        $result = $this->fbFeedPush->executeForJson();
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['feed_push_response']);
        $this->assertEquals('feed push successfully', $result['feed_push_response']);
    }
}
