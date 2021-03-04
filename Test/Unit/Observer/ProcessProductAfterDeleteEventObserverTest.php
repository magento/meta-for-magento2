<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

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
        $this->_product = $this->createMock(Product::class);
        $this->_product->expects($this->atLeastOnce())->method('getId')->will($this->returnValue("1234"));
        $this->_product->expects($this->never())->method('getSku');

        $event = $this->createPartialMock(Event::class, ['getProduct']);
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processProductAfterDeleteEventObserver =
            new ProcessProductAfterDeleteEventObserver($this->fbeHelper);
    }

    public function testExcution()
    {
        $this->fbeHelper->expects($this->atLeastOnce())->method('makeHttpRequest');
        $this->processProductAfterDeleteEventObserver->execute($this->_eventObserverMock);
    }
}
