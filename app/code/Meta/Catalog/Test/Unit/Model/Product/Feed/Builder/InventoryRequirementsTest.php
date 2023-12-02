<?php

declare(strict_types=1);

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

namespace Meta\Catalog\Test\Unit\Model\Product\Feed\Builder;

require_once __DIR__ . "/../../../../../../Model/Product/Feed/Builder/InventoryInterface.php";
require_once __DIR__ . "/../../../../../../Model/Product/Feed/Builder/InventoryRequirements.php";

use Magento\Catalog\Model\Product;
use Meta\Catalog\Model\Product\Feed\Builder\InventoryRequirements;
use PHPUnit\Framework\TestCase;

class InventoryRequirementsTest extends TestCase
{
    /**
     * @var Product
     */
    private Product $product;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->product = $this->createStub(Product::class);
    }

    public function dataProvider(): array
    {
        return [
            'grouped products: no required inventory' => ['grouped', 0, true],
            'bundle products: no required inventory' => ['grouped', 0, true],
            'simple products: require inventory, zero inventory available,' => ['simple', 0, false],
            'simple products: require inventory, non-zero inventory available,' => ['simple', 1, true],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProductsMeetInventoryRequirements(string $productTypeId, int $inventoryCount, bool $expected)
    {
        // Arrange
        $this->product->method('getTypeId')->willReturn($productTypeId);

        $inventoryMethod = 'getInventory';
        $mock = $this->getMockForAbstractClass(InventoryRequirements::class);

        $mock->expects($this->any())
            ->method($inventoryMethod)
            ->will($this->returnValue($inventoryCount));

        // Act
        $meetsInventoryRequirements = $mock->meetsInventoryRequirementsToBeInStock($this->product);

        // Assert
        $this->assertEquals($expected, $meetsInventoryRequirements);
    }
}
