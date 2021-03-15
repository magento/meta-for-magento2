<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Facebook\BusinessExtension\Observer\ProcessProductAfterSaveEventObserver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessProductAfterSaveEventObserverTest extends CommonTest
{

    protected $processProductAfterSaveEventObserver;
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
    private $store;
    /**
     * @var MockObject
     */
    private $_batchApi;

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
        $this->store = $this->createMock(StoreInterface::class);
        $this->fbeHelper->expects($this->once())->method('getStore')->will($this->returnValue($this->store));
        $this->_product = $this->createMock(Product::class);
        $this->_product->expects($this->once())->method('getId')->will($this->returnValue("1234"));
        $event = $this->createPartialMock(Event::class, ['getProduct']);
        $event->expects($this->once())->method('getProduct')->will($this->returnValue($this->_product));
        $this->_eventObserverMock = $this->createMock(Observer::class);
        $this->_eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $this->_batchApi = $this->createMock(BatchApi::class);
        $this->processProductAfterSaveEventObserver =
            new ProcessProductAfterSaveEventObserver(
                $this->fbeHelper,
                $this->_batchApi
            );
    }

    public function testExcution()
    {
        $this->_batchApi->expects($this->once())->method('buildProductRequest');
        $this->fbeHelper->expects($this->atLeastOnce())->method('makeHttpRequest');
        $this->processProductAfterSaveEventObserver->execute($this->_eventObserverMock);
    }
}