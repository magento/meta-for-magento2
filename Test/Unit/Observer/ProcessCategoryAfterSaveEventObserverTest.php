<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class ProcessCategoryAfterSaveEventObserverTest extends CommonTest
{

    protected $processCategoryAfterSaveEventObserver;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $_eventObserverMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
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
        $this->_category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getCategory']);
        $event->expects($this->once())->method('getCategory')->will($this->returnValue($this->_category));
        $this->_eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processCategoryAfterSaveEventObserver =
            new \Facebook\BusinessExtension\Observer\ProcessCategoryAfterSaveEventObserver($this->fbeHelper);
    }

    public function testExcution()
    {
        $categoryObj = $this->createMock(\Facebook\BusinessExtension\Model\Feed\CategoryCollection::class);
        $this->fbeHelper->expects($this->once())->method('getObject')->willReturn($categoryObj);
        $this->fbeHelper->expects($this->once())->method('log');

        $categoryObj->expects($this->once())->method('makeHttpRequestAfterCategorySave')->willReturn('good');
        $res = $this->processCategoryAfterSaveEventObserver->execute($this->_eventObserverMock);
        $this->assertNotNull($res);
    }
}
