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

use PHPUnit\Framework\MockObject\MockObject;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\Fbaamsettings;
use PHPUnit\Framework\TestCase;

class FbaamsettingsTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $fbeHelper;

    /**
     * @var MockObject
     */
    private $context;

    /**
     * @var MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Fbaamsettings
     */
    private $fbaamsettings;

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
        $this->request = $this->createMock(RequestInterface::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->fbaamsettings = new Fbaamsettings(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper
        );
    }

    private function setupRequestAndSettings($pixelId, $settingsAsString)
    {
        $this->request->method('getParam')
                        ->willReturn($pixelId);
        $this->fbeHelper->method('fetchAndSaveAAMSettings')->willReturn($settingsAsString);
    }

    /**
     * Test the success field in returned json is false when an invalid pixel id is sent
     *
     * @return void
     */
    public function testJsonNotSucessfullWhenInvalidPixelId()
    {
        $this->setupRequestAndSettings('1234', null);
        $result = $this->fbaamsettings->executeForJson();
        $this->assertFalse($result['success']);
        $this->assertNull($result['settings']);
    }

    /**
     * Test the success field in returned json is true when a valid pixel id is sent
     * and the response contains the json representation of the settings
     *
     * @return void
     */
    public function testJsonSucessfullWhenValidPixelId()
    {
        $pixelId = '1234';
        $settingsAsArray = [
        "enableAutomaticMatching"=>false,
        "enabledAutomaticMatchingFields"=>['em'],
        "pixelId"=>$pixelId
        ];
        $settingsAsString = json_encode($settingsAsArray);
        $this->setupRequestAndSettings($pixelId, $settingsAsString);
        $result = $this->fbaamsettings->executeForJson();
        $this->assertTrue($result['success']);
        $this->assertEquals($settingsAsString, $result['settings']);
    }
}
