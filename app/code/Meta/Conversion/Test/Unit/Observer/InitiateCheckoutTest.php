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
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Model\Quote;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\Conversion\Observer\InitiateCheckout;
use Magento\Framework\Event\Observer;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Customer\Model\AddressFactory;

class InitiateCheckoutTest extends CommonTest
{
    /**
     * @var InitiateCheckout
     */
    private InitiateCheckout $initiateCheckoutObserver;

    /**
     * @var ServerSideHelper
     */
    private ServerSideHelper $serverSideHelper;

    /**
     * @var AAMFieldsExtractorHelper
     */
    private AAMFieldsExtractorHelper $aamFieldsExtractorHelper;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * @var CustomerMetadataInterface
     */
    private CustomerMetadataInterface $customerMetadata;

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
        $this->pricingHelper = $this->createMock(PricingHelper::class);
        $this->customerMetadata = $this->createMock(CustomerMetadataInterface::class);
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
            'num_items' => 'setNumItems',
        ]);
        $this->initiateCheckoutObserver = new InitiateCheckout(
            $this->fbeHelper,
            $this->magentoDataHelper,
            $this->serverSideHelper,
            $this->serverEventFactory,
            $this->pricingHelper
        );
    }

    /**
     * Test initiate checkout event created
     *
     * @return void
     */
    public function testInitiateCheckoutEventCreated(): void
    {
        $cartContentIds = [1];
        $cartNumItems = 1;
        $eventId = '1234';
        $cartContents = [
            [
                'product_id' => 1,
                'quantity' => 1,
                'item_price' => 20
            ],
        ];

        $this->magentoDataHelper->method('getCartTotal')->willReturn(170.00);
        $this->magentoDataHelper->method('getCartNumItems')->willReturn($cartNumItems);

        $quoteMock = $this->createMock(Quote::class);

        $quoteItemMock1 = $this->createMock(Quote\Item::class);
        $productMock1 = $this->createMock(Product::class);
        $productMock1->method('getSku')->willReturn('sku1');
        $quoteItemMock1->method('getProduct')->willReturn($productMock1);
        $quoteItemMock1->method('getQty')->willReturn($cartContents[0]['quantity']);
        $quoteItemMock1->method('getId')->willReturn($cartContents[0]['product_id']);
        $quoteItemMock1->method('getPrice')->willReturn($cartContents[0]['item_price']);

        $this->magentoDataHelper
            ->method('getContentId')
            ->with($productMock1)
            ->willReturn($cartContents[0]['product_id']);

        $quoteMock->method('getAllVisibleItems')->willReturn([$quoteItemMock1]);
        $observer = new Observer([
            'eventId' => $eventId,
            'quote' => $quoteMock
        ]);

        $this->initiateCheckoutObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals($eventId, $event->getEventId());

        $customDataArray = [
            'currency' => 'USD',
            'value' => 170,
            'content_type' => 'product',
            'content_ids' => $cartContentIds,
            'contents' => $cartContents,
            'num_items' => $cartNumItems
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
        $this->assertEqualsCustomDataContents($customDataArray, $event->getCustomData());
    }
}
