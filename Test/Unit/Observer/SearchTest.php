<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Observer\Search;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

class SearchTest extends CommonTest
{

    protected $request;

    protected $searchObserver;

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
        $this->request = $this->createMock(RequestInterface::class);
        $this->searchObserver =
            new Search($this->fbeHelper, $this->serverSideHelper, $this->request);
    }

    public function testSearchEventCreated()
    {
        $this->request->method('getParam')->willReturn('Door');

        $observer = new Observer(['eventId' => '1234']);

        $this->searchObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals('1234', $event->getEventId());

        $customDataArray = [
        'search_string' => 'Door'
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
