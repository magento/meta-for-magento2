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

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\ServerEventFactory;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use PHPUnit\Framework\TestCase;

class ServerSideHelperTest extends TestCase
{
    protected $fbeHelper;

    protected $serverSideHelper;

    protected $aamFieldsExtractorHelper;

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
        $this->serverSideHelper =
        new ServerSideHelper($this->fbeHelper, $this->aamFieldsExtractorHelper);
        $this->fbeHelper->method('getAccessToken')->willReturn('abc');
    }

    public function testEventAddedToTrackedEvents()
    {
        $event = ServerEventFactory::createEvent('ViewContent', []);
        $this->aamFieldsExtractorHelper->method('setUserData')->willReturn($event);
        $this->serverSideHelper->sendEvent($event);
        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
        $event = $this->serverSideHelper->getTrackedEvents()[0];
        $this->assertEquals('ViewContent', $event->getEventName());
    }
}
