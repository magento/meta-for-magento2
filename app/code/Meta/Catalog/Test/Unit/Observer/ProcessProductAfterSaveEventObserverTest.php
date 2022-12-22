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

namespace Meta\Catalog\Test\Unit\Observer;

use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Test\Unit\Observer\CommonTest;
use Meta\Catalog\Model\Product\Feed\Method\BatchApi;
use Meta\Catalog\Observer\Product\SaveAfter as ProcessProductAfterSaveEventObserver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessProductAfterSaveEventObserverTest extends CommonTest
{
    protected $processProductAfterSaveEventObserver;

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
    private $store;

    /**
     * @var MockObject
     */
    private $_batchApi;


    /**
     * @var MockObject
     */
    private $_graphApi;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->store = $this->createMock(StoreInterface::class);
        $this->fbeHelper->expects($this->once())->method('getStore')->will($this->returnValue($this->store));
        $this->_product = $this->createMock(Product::class);
        $this->_product->expects($this->once())->method('getId')->will($this->returnValue("1234"));
        $event = $this->getMockBuilder(Event::class)->addMethods(['getProduct'])->getMock();
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->_graphApi = $this->createMock(GraphAPIAdapter::class);
        $this->_batchApi = $this->createMock(BatchApi::class);
        $this->processProductAfterSaveEventObserver =
            new ProcessProductAfterSaveEventObserver(
                $this->systemConfig,
                $this->fbeHelper,
                $this->_batchApi,
                $this->_graphApi,
            );
    }

    public function testExecution()
    {
        $this->systemConfig->method('isActiveIncrementalProductUpdates')->willReturn(true);
        $this->_batchApi->expects($this->once())->method('buildRequestForIndividualProduct');
        $this->fbeHelper->expects($this->atLeastOnce())->method('makeHttpRequest');
        $this->processProductAfterSaveEventObserver->execute($this->_eventObserverMock);
    }
}
