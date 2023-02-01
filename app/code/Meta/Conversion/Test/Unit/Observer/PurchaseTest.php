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

namespace Meta\Conversion\Test\Unit\Observer;

use Meta\BusinessExtension\Test\Unit\Observer\CommonTest;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\Conversion\Observer\Purchase;
use Magento\Framework\Event\Observer;

class PurchaseTest extends CommonTest
{
    protected $purchaseObserver;

    protected $serverSideHelper;

    private $aamFieldsExtractorHelper;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->aamFieldsExtractorHelper = new AAMFieldsExtractorHelper(
            $this->magentoDataHelper,
            $this->fbeHelper
        );
        $this->serverSideHelper = new ServerSideHelper(
            $this->fbeHelper,
            $this->aamFieldsExtractorHelper,
            $this->systemConfig
        );
        $this->purchaseObserver =
            new Purchase(
                $this->fbeHelper,
                $this->magentoDataHelper,
                $this->serverSideHelper
            );
    }

    public function testPurchaseEventCreated()
    {
        $this->magentoDataHelper->method('getOrderTotal')->willReturn(170.00);
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
