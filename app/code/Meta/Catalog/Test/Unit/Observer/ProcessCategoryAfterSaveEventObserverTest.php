<?php

declare(strict_types=1);

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

use Magento\Framework\Event;
use Magento\Framework\Message\ManagerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Category\CategoryCollection;
use Meta\Catalog\Observer\ProcessCategoryAfterSaveEventObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessCategoryAfterSaveEventObserverTest extends TestCase
{
    /**
     * @var ProcessCategoryAfterSaveEventObserver
     */
    public $processCategoryAfterSaveEventObserver;

    /**
     * @var MockObject
     */
    private MockObject $fbeHelper;

    /**
     * @var MockObject
     */
    private MockObject $categoryCollection;

    /**
     * @var MockObject
     */
    private $eventObserverMock;

    /**
     * @var MockObject
     */
    private $category;

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
        $this->categoryCollection = $this->createMock(CategoryCollection::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['dataHasChangedFor'])->getMock();
        $this->category->expects($this->once())
            ->method('dataHasChangedFor')
            ->will($this->returnValue(true));
        $event = $this->getMockBuilder(Event::class)->addMethods(['getCategory'])->getMock();
        $event->expects($this->once())->method('getCategory')->will($this->returnValue($this->category));
        $this->eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processCategoryAfterSaveEventObserver = new ProcessCategoryAfterSaveEventObserver(
            $this->fbeHelper,
            $this->categoryCollection,
            $this->messageManager
        );
    }

    public function testExecution()
    {

        $this->fbeHelper->expects($this->once())->method('log');

        $this->categoryCollection
            ->expects($this->once())
            ->method('makeHttpRequestsAfterCategorySave');
        $this->processCategoryAfterSaveEventObserver->execute($this->eventObserverMock);
    }
}
