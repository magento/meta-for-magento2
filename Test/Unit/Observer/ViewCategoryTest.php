<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Observer;

use Facebook\BusinessExtension\Observer\ViewCategory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;

class ViewCategoryTest extends CommonTest
{

    protected $registry;

    protected $viewCategoryObserver;

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
        $this->viewCategoryObserver =
            new ViewCategory(
                $this->fbeHelper,
                $this->serverSideHelper,
                $this->registry
            );
    }

    public function testViewCategoryEventCreated()
    {
        $category = $this->objectManager->getObject('Magento\Catalog\Model\Category');
        $category->setName('Electronics');
        $this->registry->method('registry')->willReturn($category);

        $observer = new Observer(['eventId' => '1234']);

        $this->viewCategoryObserver->execute($observer);

        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

        $event = $this->serverSideHelper->getTrackedEvents()[0];

        $this->assertEquals('1234', $event->getEventId());

        $customDataArray = [
        'content_category' => 'Electronics'
        ];

        $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
    }
}
