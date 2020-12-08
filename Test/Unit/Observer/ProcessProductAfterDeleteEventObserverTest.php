<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class ProcessProductAfterDeleteEventObserverTest extends CommonTest{

    protected $processProductAfterDeleteEventObserver;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $_eventObserverMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $_product;

    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown() {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->_product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->_product->expects($this->once())->method('getId')->will($this->returnValue("1234"));
        $this->_product->expects($this->atLeastOnce())->method('getSku')->will($this->returnValue("testSku"));

        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getProduct']);
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->processProductAfterDeleteEventObserver = new \Facebook\BusinessExtension\Observer\ProcessProductAfterDeleteEventObserver($this->fbeHelper);
    }

    public function testExcution(){
        $this->fbeHelper->expects($this->atLeastOnce())->method('makeHttpRequest');
        $res = $this->processProductAfterDeleteEventObserver->execute($this->_eventObserverMock);
        $this->assertNull($res);
    }
}
