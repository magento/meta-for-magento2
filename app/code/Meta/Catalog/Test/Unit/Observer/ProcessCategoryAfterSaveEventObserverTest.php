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

use Magento\Framework\Event;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Feed\CategoryCollection;
use Meta\Catalog\Observer\ProcessCategoryAfterSaveEventObserver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessCategoryAfterSaveEventObserverTest extends TestCase
{
    /**
     * @var ProcessCategoryAfterSaveEventObserver
     */
    protected $processCategoryAfterSaveEventObserver;

    /**
     * @var MockObject
     */
    private MockObject $fbeHelper;

    /**
     * @var MockObject
     */
    private $eventObserverMock;

    /**
     * @var MockObject
     */
    private $category;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $event = $this->getMockBuilder(Event::class)->addMethods(['getCategory'])->getMock();
        $event->expects($this->once())->method('getCategory')->will($this->returnValue($this->category));
        $this->eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processCategoryAfterSaveEventObserver =
            new ProcessCategoryAfterSaveEventObserver($this->fbeHelper);
    }

    public function testExecution()
    {
        $categoryObj = $this->createMock(CategoryCollection::class);
        $this->fbeHelper->expects($this->once())->method('getObject')->willReturn($categoryObj);
        $this->fbeHelper->expects($this->once())->method('log');

        $categoryObj->expects($this->once())->method('makeHttpRequestAfterCategorySave')->willReturn('good');
        $res = $this->processCategoryAfterSaveEventObserver->execute($this->eventObserverMock);
        $this->assertNotNull($res);
    }
}
