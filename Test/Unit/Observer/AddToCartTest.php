<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Observer\AddToCart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

class AddToCartTest extends CommonTest
{
    protected $request;

    protected $addToCartObserver;

    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->request = $this->createMock(RequestInterface::class);
        $this->addToCartObserver = new AddToCart(
            $this->fbeHelper,
            $this->magentoDataHelper,
            $this->serverSideHelper,
            $this->request
        );
    }

    public function testAddToCartEventCreated()
    {
        $id = 123;
        $sku = 'SKU-123';
        $contentType = 'product';
        $eventId = '1234';

        $this->magentoDataHelper->method('getValueForProduct')->willReturn(12.99);
        $this->magentoDataHelper->method('getCategoriesForProduct')->willReturn('Electronics');
        $this->magentoDataHelper->method('getContentId')->willReturn($sku);
        $this->magentoDataHelper->method('getContentType')->willReturn($contentType);

        $product = $this->objectManager->getObject('\Magento\Catalog\Model\Product');
        $product->setId($id)->setSku($sku);
        $product->setName('Earphones');
        $this->request->method('getParam')->willReturn($sku);
        $this->magentoDataHelper->method('getProductBySku')->willReturn($product);

        $observer = new Observer(['eventId' => $eventId]);

        $this->addToCartObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals($eventId, $event->getEventId());

        $customDataArray = [
            'currency' => 'USD',
            'value' => 12.99,
            'content_type' => $contentType,
            'content_ids' => [$sku],
            'content_category' => 'Electronics',
            'content_name' => 'Earphones'
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
