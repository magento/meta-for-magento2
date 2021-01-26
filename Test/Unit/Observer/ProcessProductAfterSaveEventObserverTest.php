<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class ProcessProductAfterSaveEventObserverTest extends CommonTest{

    protected $processProductAfterSaveEventObserver;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $_eventObserverMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $_product;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $_batchApi;

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
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getProduct']);
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->_batchApi = $this->createMock(\Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi::class);
        $this->processProductAfterSaveEventObserver = new \Facebook\BusinessExtension\Observer\ProcessProductAfterSaveEventObserver($this->fbeHelper, $this->_batchApi);
    }

    public function testExcution(){
        $this->_batchApi->expects($this->once())->method('buildProductRequest');
        $this->fbeHelper->expects($this->atLeastOnce())->method('makeHttpRequest');
        $res = $this->processProductAfterSaveEventObserver->execute($this->_eventObserverMock);
        $this->assertNull($res);
    }
}
