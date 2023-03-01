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

use FacebookAds\Object\ServerSide\AdsPixelSettings;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\Fbprofiles;
use PHPUnit\Framework\TestCase;

class FbprofilesTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $fbeHelper;

    /**
     * @var MockObject
     */
    private $systemConfig;

    /**
     * @var MockObject
     */
    private $context;

    /**
     * @var MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Fbprofiles
     */
    private $fbprofiles;

    /**
     * @var MockObject
     */
    private $request;

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
        $this->fbprofiles = new Fbprofiles(
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
    public function testExecuteForJson()
    {
        $profiles = '1234';
        $this->request->method('getParam')
            ->willReturn($profiles);
        $this->systemConfig->method('getProfiles')
            ->willReturn($profiles);
        $result = $this->fbprofiles->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($profiles, $result['profiles']);
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNoProfiles()
    {
        $profiles = '1234';
        $this->request->method('getParam')->willReturn(null);
        $this->systemConfig->method('getProfiles')
            ->willReturn($profiles);
        $result = $this->fbprofiles->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($profiles, $result['profiles']);
    }
}
