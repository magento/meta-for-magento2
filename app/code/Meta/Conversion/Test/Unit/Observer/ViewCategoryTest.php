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
use Meta\Conversion\Observer\ViewCategory;
use Magento\Framework\Event\Observer;

class ViewCategoryTest extends CommonTest
{
    protected $viewCategoryObserver;

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
        $this->viewCategoryObserver =
            new ViewCategory(
                $this->fbeHelper,
                $this->serverSideHelper,
            );
    }

    public function testViewCategoryEventCreated()
    {
        $category = $this->objectManager->getObject('Magento\Catalog\Model\Category');
        $category->setName('Electronics');

        $observer = new Observer(['eventId' => '1234', 'category' => $category]);

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
