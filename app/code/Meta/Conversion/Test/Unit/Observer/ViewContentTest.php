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
use Meta\Conversion\Observer\ViewContent;
use Magento\Framework\Event\Observer;

class ViewContentTest extends CommonTest
{
    protected $viewContentObserver;

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
        $this->viewContentObserver =
            new ViewContent(
                $this->fbeHelper,
                $this->serverSideHelper,
                $this->magentoDataHelper,
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

        $observer = new Observer(['eventId' => $eventId, 'product' => $product]);

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
