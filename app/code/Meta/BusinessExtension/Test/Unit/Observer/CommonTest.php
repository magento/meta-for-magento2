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

namespace Meta\BusinessExtension\Test\Unit\Observer;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\MagentoDataHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

abstract class CommonTest extends TestCase
{
    protected $magentoDataHelper;

    protected $fbeHelper;

    protected $systemConfig;

    protected $objectManager;
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
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(\Meta\BusinessExtension\Model\System\Config::class);
        $this->magentoDataHelper = $this->createMock(MagentoDataHelper::class);
        $this->objectManager = new ObjectManager($this);
        $this->fbeHelper->method('getAccessToken')->willReturn('');
        $this->fbeHelper->method('getPixelId')->willReturn('123');
        $this->magentoDataHelper->method('getCurrency')->willReturn('USD');
    }

    public function assertEqualsCustomData($customDataArray, $customData)
    {
        if (!empty($customDataArray['currency'])) {
            $this->assertEquals($customData->getCurrency(), $customDataArray['currency']);
        }

        if (!empty($customDataArray['value'])) {
            $this->assertEquals($customData->getValue(), $customDataArray['value']);
        }

        if (!empty($customDataArray['content_ids'])) {
            $this->assertEquals($customData->getContentIds(), $customDataArray['content_ids']);
        }

        if (!empty($customDataArray['content_type'])) {
            $this->assertEquals($customData->getContentType(), $customDataArray['content_type']);
        }

        if (!empty($customDataArray['content_name'])) {
            $this->assertEquals($customData->getContentName(), $customDataArray['content_name']);
        }

        if (!empty($customDataArray['content_category'])) {
            $this->assertEquals($customData->getContentCategory(), $customDataArray['content_category']);
        }

        if (!empty($customDataArray['search_string'])) {
            $this->assertEquals($customData->getSearchString(), $customDataArray['search_string']);
        }

        if (!empty($customDataArray['num_items'])) {
            $this->assertEquals($customData->getNumItems(), $customDataArray['num_items']);
        }

        if (!empty($customDataArray['order_id'])) {
            $this->assertEquals($customData->getOrderId(), $customDataArray['order_id']);
        }

        if (!empty($customDataArray['contents'])) {
            $contents = $customData->getContents();
            $this->assertNotNull($contents);
            $this->assertEquals(count($customDataArray['contents']), count($contents));
            for ($i = 0; $i < count($contents); $i++) {
                if (!empty($customDataArray['contents'][$i]['product_id'])) {
                    $this->assertEquals($customDataArray['contents'][$i]['product_id'], $contents[$i]->getProductId());
                }
                if (!empty($customDataArray['contents'][$i]['quantity'])) {
                    $this->assertEquals($customDataArray['contents'][$i]['quantity'], $contents[$i]->getQuantity());
                }
                if (!empty($customDataArray['contents'][$i]['item_price'])) {
                    $this->assertEquals($customDataArray['contents'][$i]['item_price'], $contents[$i]->getItemPrice());
                }
            }
        }
    }
}