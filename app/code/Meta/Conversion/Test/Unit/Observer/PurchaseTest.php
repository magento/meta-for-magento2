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

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\Order;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\Conversion\Observer\Purchase;
use Magento\Framework\Event\Observer;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Customer\Model\AddressFactory;

class PurchaseTest extends CommonTest
{
    /**
     * @var Purchase
     */
    protected Purchase $purchaseObserver;

    /**
     * @var ServerSideHelper
     */
    protected ServerSideHelper $serverSideHelper;

    /**
     * @var AAMFieldsExtractorHelper
     */
    private AAMFieldsExtractorHelper $aamFieldsExtractorHelper;

    /**
     * @var CustomerMetadataInterface
     */
    private CustomerMetadataInterface $customerMetadata;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * @var AddressFactory
     */
    private AddressFactory $addressFactory;

    /**
     * @var ServerEventFactory
     */
    private ServerEventFactory $serverEventFactory;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->customerMetadata = $this->createMock(CustomerMetadataInterface::class);
        $this->pricingHelper = $this->createMock(PricingHelper::class);
        $this->addressFactory = $this->createMock(AddressFactory::class);

        $this->aamFieldsExtractorHelper = new AAMFieldsExtractorHelper(
            $this->magentoDataHelper,
            $this->fbeHelper,
            $this->customerMetadata,
            $this->addressFactory
        );
        $this->serverSideHelper = new ServerSideHelper(
            $this->fbeHelper,
            $this->aamFieldsExtractorHelper,
            $this->systemConfig
        );
        $httpRequestMock = $this->createMock(Http::class);
        $httpRequestMock->method('getServerValue')->willReturn([]);
        $this->serverEventFactory = new ServerEventFactory($httpRequestMock, [
            'currency' => 'setCurrency',
            'value' => 'setValue',
            'content_type' => 'setContentType',
            'content_ids' => 'setContentIds',
            'contents' => 'setContents',
            'order_id' => 'setOrderId',
        ]);
        $this->purchaseObserver =
            new Purchase(
                $this->fbeHelper,
                $this->magentoDataHelper,
                $this->serverSideHelper,
                $this->serverEventFactory,
                $this->customerMetadata,
                $this->pricingHelper
            );
    }

    public function testPurchaseEventCreated()
    {
        $subTotal = 40.00;
        $orderContentIds = [1];
        $orderContents = [
            ['product_id' => 1, 'quantity' => 2, 'item_price' => 40],
        ];
        $eventId = '1234';

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getId')->willReturn(1);
        $orderMock->method('getSubTotal')->willReturn($subTotal);

        $orderItemMock1 = $this->createMock(Order\Item::class);
        $orderItemProduct1 = $this->createMock(Product::class);
        $orderItemMock1->method('getProduct')->willReturn($orderItemProduct1);
        $orderItemMock1->method('getPrice')->willReturn($orderContents[0]['item_price']);
        $orderItemMock1->method('getQtyOrdered')->willReturn($orderContents[0]['quantity']);
        $orderMock->method('getAllVisibleItems')->willReturn([$orderItemMock1]);

        $this->magentoDataHelper->method('getContentId')
            ->with($orderItemProduct1)
            ->willReturn($orderContents[0]['product_id']);

        $observer = new Observer([
            'eventId' => $eventId,
            'lastOrder' => $orderMock
        ]);

        $this->pricingHelper->method('currency')->willReturn($subTotal);

        $this->purchaseObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals($eventId, $event->getEventId());

        $customDataArray = [
            'currency' => 'USD',
            'value' => 40,
            'content_type' => 'product',
            'content_ids' => $orderContentIds,
            'contents' => $orderContents,
            'order_id' => 1
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
        $this->assertEqualsCustomDataContents($customDataArray, $event->getCustomData());
    }
}
