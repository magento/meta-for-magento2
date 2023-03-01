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

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config;
use Meta\Catalog\Helper\Product\Identifier;
use Meta\Catalog\Observer\Product\DeleteAfter as ProcessProductAfterDeleteEventObserver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Message\ManagerInterface;

class ProcessProductAfterDeleteEventObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    private MockObject $fbeHelper;

    /**
     * @var MockObject
     */
    private MockObject $systemConfig;

    /**
     * @var ProcessProductAfterDeleteEventObserver
     */
    private $processProductAfterDeleteEventObserver;

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
    private $_graphApi;

    /**
     * @var MockObject
     */
    private $identifier;

    /**
     * @var MockObject
     */
    private $messageManager;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(Config::class);
        $this->_product = $this->createMock(Product::class);
        $this->_product->expects($this->atLeastOnce())->method('getId')->will($this->returnValue("1234"));
        $this->_product->expects($this->never())->method('getSku');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $store = $this->createMock(StoreInterface::class);
        $this->systemConfig->method('getStoreManager')->willReturn($storeManager);
        $storeManager->method('getStores')->willReturn([$store]);
        $store->method('getId')->willReturn('1');

        $event = $this->getMockBuilder(Event::class)->addMethods(['getProduct'])->getMock();
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->_graphApi = $this->createMock(GraphAPIAdapter::class);
        $this->identifier = $this->createMock(Identifier::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->processProductAfterDeleteEventObserver = new ProcessProductAfterDeleteEventObserver(
            $this->systemConfig,
            $this->_graphApi,
            $this->fbeHelper,
            $this->identifier,
            $this->messageManager,
        );
    }

    public function testExecution()
    {
        $this->systemConfig->method('isActiveExtension')->willReturn(true);
        $this->systemConfig->method('isActiveIncrementalProductUpdates')->willReturn(true);
        $this->_graphApi->expects($this->atLeastOnce())->method('catalogBatchRequest');
        $this->systemConfig->method('isActiveExtension')->willReturn(true);
        $this->processProductAfterDeleteEventObserver->execute($this->_eventObserverMock);
    }
}
