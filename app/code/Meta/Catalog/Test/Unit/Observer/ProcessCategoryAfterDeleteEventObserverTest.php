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

use Magento\Framework\Event\Observer;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config;
use Meta\Catalog\Model\Feed\CategoryCollection;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event;
use Meta\Catalog\Observer\ProcessCategoryAfterDeleteEventObserver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessCategoryAfterDeleteEventObserverTest extends TestCase
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
     * @var ProcessCategoryAfterDeleteEventObserver
     */
    private $processCategoryAfterDeleteEventObserver;

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
        $this->systemConfig = $this->createMock(Config::class);
        $this->category = $this->createMock(Category::class);
        $event = $this->getMockBuilder(Event::class)->addMethods(['getCategory'])->getMock();
        $event->expects($this->once())->method('getCategory')->will($this->returnValue($this->category));
        $this->eventObserverMock = $this->createMock(Observer::class);
        $this->eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processCategoryAfterDeleteEventObserver =
            new ProcessCategoryAfterDeleteEventObserver(
                $this->fbeHelper,
                $this->systemConfig
            );
    }

    public function testExecution()
    {
        $this->systemConfig->method('isActiveIncrementalProductUpdates')->willReturn(true);
        $categoryObj = $this->createMock(CategoryCollection::class);
        $this->fbeHelper->expects($this->once())->method('getObject')->willReturn($categoryObj);
        $this->fbeHelper->expects($this->once())->method('log')->willReturn(null);

        $categoryObj->expects($this->once())->method('deleteCategoryAndSubCategoryFromFB')->willReturn('good');
        $this->processCategoryAfterDeleteEventObserver->execute($this->eventObserverMock);
    }
}
