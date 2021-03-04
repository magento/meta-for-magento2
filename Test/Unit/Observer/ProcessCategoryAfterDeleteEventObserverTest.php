<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessCategoryAfterDeleteEventObserverTest extends CommonTest
{

    protected $processCategoryAfterDeleteEventObserver;
    /**
     * @var MockObject
     */
    private $_eventObserverMock;
    /**
     * @var MockObject
     */
    private $_category;

    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_category = $this->createMock(Category::class);
        $event = $this->createPartialMock(Event::class, ['getCategory']);
        $event->expects($this->once())->method('getCategory')->will($this->returnValue($this->_category));
        $this->_eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processCategoryAfterDeleteEventObserver =
            new \Facebook\BusinessExtension\Observer\ProcessCategoryAfterDeleteEventObserver($this->fbeHelper);
    }

    public function testExcution()
    {
        $categoryObj = $this->createMock(CategoryCollection::class);
        $this->fbeHelper->expects($this->once())->method('getObject')->willReturn($categoryObj);
        $this->fbeHelper->expects($this->once())->method('log')->willReturn(null);

        $categoryObj->expects($this->once())->method('deleteCategoryAndSubCategoryFromFB')->willReturn('good');
        $this->processCategoryAfterDeleteEventObserver->execute($this->_eventObserverMock);
    }
}
