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

namespace Meta\Conversion\Test\Unit\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\Conversion\Helper\ServerSideHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServerSideHelperTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $fbeHelper;

    /**
     * @var MockObject
     */
    private $serverSideHelper;

    /**
     * @var MockObject
     */
    private $aamFieldsExtractorHelper;

    /**
     * @var MockObject
     */
    private $systemConfig;

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
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->aamFieldsExtractorHelper =
        $this->createMock(AAMFieldsExtractorHelper::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->systemConfig->method('getAccessToken')->willReturn('abc');
        $this->serverSideHelper = new ServerSideHelper(
            $this->fbeHelper,
            $this->aamFieldsExtractorHelper,
            $this->systemConfig,
        );
    }

    /**
     * @return void
     */
    public function testEventAddedToTrackedEvents(): void
    {
        $httpRequestMock = $this->createMock(Http::class);
        $httpRequestMock->method('getServerValue')->willReturn([]);
        $serverEventFactory = new ServerEventFactory($httpRequestMock, []);
        $event = $serverEventFactory->createEvent('ViewContent', []);
        $this->aamFieldsExtractorHelper->method('setUserData')->willReturn($event);
        $this->serverSideHelper->sendEvent($event);
        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
        $event = $this->serverSideHelper->getTrackedEvents()[0];
        $this->assertEquals('ViewContent', $event->getEventName());
    }
}
