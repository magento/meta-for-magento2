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
require_once __DIR__ . "/../../../../../../Model/Product/Feed/Builder/Inventory.php";
require_once __DIR__ . "/../../../../../../../BusinessExtension/Model/System/Config.php";


use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Builder\Inventory;
use Meta\Catalog\Model\Product\Feed\Builder\InventoryInterface;
use PHPUnit\Framework\TestCase;

class InventoryTest extends TestCase
{
    /**
     * @var Inventory
     */
    private $inventory;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->systemConfig = $this->createStub(SystemConfig::class);

        $stockItemRepository = $this->createStub(StockItemRepositoryInterface::class);
        $stockItemCriteriaInterfaceFactory = $this->createStub(StockItemCriteriaInterfaceFactory::class);

        $this->inventory = $this->getMockBuilder(Inventory::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$stockItemRepository, $stockItemCriteriaInterfaceFactory, $this->systemConfig])
            ->onlyMethods(['getStockItem'])
            ->getMock();
    }

    public function testGetAvailabilityWhenProductStockIsNotSet()
    {
        // Act
        $actual = $this->inventory->getAvailability();

        // Assert
        $this->assertEquals('out of stock', $actual);
    }

    public function testGetInventoryWhenProductStockIsNotSet()
    {
        // Act
        $actual = $this->inventory->getInventory();

        // Assert
        $this->assertEquals(0, $actual);
    }

    public function dataProvider(): array
    {
        return [
            'unmanaged stock: in-stock, inventory=-1' => [
                // Managed Stock
                false,
                // OOS Threshold
                0,
                // Item inventory
                0,
                // Is in stock
                false,
                // Expected results
                InventoryInterface::STATUS_IN_STOCK, InventoryInterface::UNMANAGED_STOCK_QTY
            ],
            'managed stock, no inventory, not in stock: out-of-stock, inventory=0' => [
                // Managed Stock
                true,
                // OOS Threshold
                10,
                // Item inventory
                0,
                // Is in stock
                false,
                // Expected results
                InventoryInterface::STATUS_OUT_OF_STOCK, 0
            ],
            'managed stock, inventory below threshold, not in stock: out-of-stock, inventory=0' => [
                // Managed Stock
                true,
                // OOS Threshold
                10,
                // Item inventory
                5,
                // Is in stock
                false,
                // Expected results
                InventoryInterface::STATUS_OUT_OF_STOCK, 0
            ],
            'managed stock, inventory above threshold, not in stock: out-of-stock, inventory=5' => [
                // Managed Stock
                true,
                // OOS Threshold
                10,
                // Item inventory
                15,
                // Is in stock
                false,
                // Expected results
                InventoryInterface::STATUS_OUT_OF_STOCK, 5
            ],
            'managed stock, no inventory, in stock: out-of-stock, inventory=0' => [
                // Managed Stock
                true,
                // OOS Threshold
                10,
                // Item inventory
                0,
                // Is in stock
                true,
                // Expected results
                InventoryInterface::STATUS_OUT_OF_STOCK, 0
            ],
            'managed stock, inventory below threshold, in stock: out-of-stock, inventory=0' => [
                // Managed Stock
                true,
                // OOS Threshold
                10,
                // Item inventory
                5,
                // Is in stock
                true,
                // Expected results
                InventoryInterface::STATUS_OUT_OF_STOCK, 0
            ],
            'managed stock, inventory above threshold, in stock: in-stock, inventory=5' => [
                // Managed Stock
                true,
                // OOS Threshold
                10,
                // Item inventory
                15,
                // Is in stock
                true,
                // Expected results
                InventoryInterface::STATUS_IN_STOCK, 5
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetInventory(
        bool   $manageStock,
        int    $oosThreshold,
        int    $qty,
        bool   $isInStock,
        string $expected_availability,
        int    $expected_inventory
    ) {
        // Arrange
        $product = $this->createStub(Product::class);
        $productStock = $this->createStub(StockItemInterface::class);

        $productStock->method('getManageStock')
            ->willReturn($manageStock);
        $productStock->method('getQty')
            ->willReturn($qty);
        $productStock->method('getIsInStock')
            ->willReturn($isInStock);

        $this->inventory->method('getStockItem')
            ->willReturn($productStock);

        $this->systemConfig->method('getOutOfStockThreshold')
            ->willReturn($oosThreshold);

        $inventory = $this->inventory->initInventoryForProduct($product);

        // Act
        $actual_availability = $inventory->getAvailability();
        $actual_inventory = $inventory->getInventory();

        // Assert
        $this->assertEquals($expected_availability, $actual_availability);
        $this->assertEquals($expected_inventory, $actual_inventory);
    }
}
