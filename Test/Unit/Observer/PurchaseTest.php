<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Observer\Purchase;
use Magento\Framework\Event\Observer;

class PurchaseTest extends CommonTest
{

    protected $purchaseObserver;

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
        $this->purchaseObserver =
            new Purchase(
                $this->fbeHelper,
                $this->magentoDataHelper,
                $this->serverSideHelper
            );
    }

    public function testPurchaseEventCreated()
    {
        $this->magentoDataHelper->method('getOrderTotal')->willReturn(170);
        $this->magentoDataHelper->method('getOrderContentIds')->willReturn(
            [1, 2]
        );
        $this->magentoDataHelper->method('getOrderContents')->willReturn(
            [
            [ 'product_id'=>1, 'quantity'=>1, 'item_price' =>20 ],
            [ 'product_id'=>2, 'quantity'=>3, 'item_price' =>50 ]
            ]
        );
        $this->magentoDataHelper->method('getOrderId')->willReturn(1);

        $observer = new Observer(['eventId' => '1234']);

        $this->purchaseObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals('1234', $event->getEventId());

        $customDataArray = [
        'currency' => 'USD',
        'value' => 170,
        'content_type' => 'product',
        'content_ids' => [1, 2],
        'contents' => [
        [ 'product_id'=>1, 'quantity'=>1, 'item_price' =>20 ],
        [ 'product_id'=>2, 'quantity'=>3, 'item_price' =>50 ]
        ],
        'order_id' => 1
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
