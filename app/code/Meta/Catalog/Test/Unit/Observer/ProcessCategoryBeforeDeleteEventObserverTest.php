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

use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Feed\CategoryCollection;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event;
use Meta\Catalog\Observer\ProcessCategoryBeforeDeleteEventObserver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessCategoryBeforeDeleteEventObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    private MockObject $fbeHelper;

    /**
     * @var MockObject
     */
    private MockObject $categoryCollection;

    /**
     * @var ProcessCategoryBeforeDeleteEventObserver
     */
    private $processCategoryBeforeDeleteEventObserver;

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
            ->onlyMethods(['getName'])->getMock();
        $this->category->expects($this->once())
            ->method('getName')
            ->will($this->returnValue("Test Category"));
        $event = $this->getMockBuilder(Event::class)->addMethods(['getCategory'])->getMock();
        $event->expects($this->once())->method('getCategory')->will($this->returnValue($this->category));
        $this->eventObserverMock = $this->createMock(Observer::class);
        $this->eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processCategoryBeforeDeleteEventObserver =
            new ProcessCategoryBeforeDeleteEventObserver(
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
            ->method('deleteCategoryAndSubCategoryFromFB');
        $this->processCategoryBeforeDeleteEventObserver->execute($this->eventObserverMock);
    }
}
