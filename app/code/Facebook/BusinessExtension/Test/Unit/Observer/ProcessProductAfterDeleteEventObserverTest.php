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

use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Facebook\BusinessExtension\Observer\ProcessProductAfterDeleteEventObserver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessProductAfterDeleteEventObserverTest extends CommonTest
{
    protected $processProductAfterDeleteEventObserver;

    /**
     * @var MockObject
     */
    private $_eventObserverMock;

    /**
     * @var MockObject
     */
    private $_product;

    /**
     * @var MockObject
     */
    private $_batchApi;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_product = $this->createMock(Product::class);
        $this->_product->expects($this->atLeastOnce())->method('getId')->will($this->returnValue("1234"));
        $this->_product->expects($this->never())->method('getSku');

        $event = $this->getMockBuilder(Event::class)->addMethods(['getProduct'])->getMock();
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->_batchApi = $this->createMock(BatchApi::class);
        $this->processProductAfterDeleteEventObserver =
            new ProcessProductAfterDeleteEventObserver($this->fbeHelper, $this->_batchApi, $this->systemConfig);
    }

    public function testExecution()
    {
        $this->systemConfig->method('isActiveIncrementalProductUpdates')->willReturn(true);
        $this->fbeHelper->expects($this->atLeastOnce())->method('makeHttpRequest');
        $this->processProductAfterDeleteEventObserver->execute($this->_eventObserverMock);
    }
}
