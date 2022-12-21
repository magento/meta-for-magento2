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

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Observer\Search;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

class SearchTest extends CommonTest
{
    protected $request;

    protected $searchObserver;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->request = $this->createMock(RequestInterface::class);
        $this->searchObserver =
            new Search($this->fbeHelper, $this->serverSideHelper, $this->request);
    }

    public function testSearchEventCreated()
    {
        $this->request->method('getParam')->willReturn('Door');

        $observer = new Observer(['eventId' => '1234']);

        $this->searchObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals('1234', $event->getEventId());

        $customDataArray = [
        'search_string' => 'Door'
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
