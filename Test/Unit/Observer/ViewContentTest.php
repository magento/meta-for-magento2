<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Observer\ViewContent;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;

class ViewContentTest extends CommonTest
{
    protected $registry;

    protected $viewContentObserver;

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
        $this->registry = $this->createMock(Registry::class);
        $this->viewContentObserver =
            new ViewContent(
                $this->fbeHelper,
                $this->serverSideHelper,
                $this->magentoDataHelper,
                $this->registry
            );
    }

    public function testViewContentEventCreated()
    {
        $id = 123;
        $sku = 'SKU-123';
        $contentType = 'product_group';
        $eventId = '1234';

        $this->magentoDataHelper->method('getValueForProduct')->willReturn(12.99);
        $this->magentoDataHelper->method('getCategoriesForProduct')->willReturn('Electronics');
        $this->magentoDataHelper->method('getContentId')->willReturn($id);
        $this->magentoDataHelper->method('getContentType')->willReturn($contentType);

        $product = $this->objectManager->getObject('\Magento\Catalog\Model\Product');
        $product->setId($id)->setSku($sku);
        $product->setName('Earphones');
        $this->registry->method('registry')->willReturn($product);

        $observer = new Observer(['eventId' => $eventId]);

        $this->viewContentObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals($eventId, $event->getEventId());

        $customDataArray = [
            'currency' => 'USD',
            'value' => 12.99,
            'content_type' => $contentType,
            'content_ids' => [$id],
            'content_category' => 'Electronics',
            'content_name' => 'Earphones'
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
