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
        $this->magentoDataHelper->method('getValueForProduct')->willReturn(12.99);
        $this->magentoDataHelper->method('getCategoriesForProduct')->willReturn('Electronics');
        $product = $this->objectManager->getObject('\Magento\Catalog\Model\Product');
        $product->setId(123);
        $product->setName('Earphones');
        $this->registry->method('registry')->willReturn($product);

        $observer = new Observer(['eventId' => '1234']);

        $this->viewContentObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals('1234', $event->getEventId());

        $customDataArray = [
        'currency' => 'USD',
        'value' => 12.99,
        'content_type' => 'product',
        'content_ids' => [123],
        'content_category' => 'Electronics',
        'content_name' => 'Earphones'
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
