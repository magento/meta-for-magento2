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

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbpixelTest extends \PHPUnit\Framework\TestCase
{
    protected $fbeHelper;

    protected $systemConfig;

    protected $context;

    protected $resultJsonFactory;

    protected $fbPixelTest;

    protected $request;

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
        $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
        $this->systemConfig = $this->createMock(\Facebook\BusinessExtension\Model\System\Config::class);
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->fbPixelTest = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbpixel(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper,
            $this->systemConfig
        );
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNoPixel()
    {
        $pixelId = '1234';
        $this->request->method('getParam')
            ->willReturn(null);
        $this->systemConfig->method('getPixelId')
            ->willReturn($pixelId);
        $result = $this->fbPixelTest->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($pixelId, $result['pixelId']);
    }
}
