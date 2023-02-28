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
namespace Meta\Catalog\Model\Product\Feed\Builder;

use Magento\Catalog\Model\Product;

interface InventoryInterface
{
    public const STATUS_IN_STOCK = 'in stock';

    public const STATUS_OUT_OF_STOCK = 'out of stock';

    public const UNMANAGED_STOCK_QTY = 9999;

    /**
     * Init inventory for product
     *
     * @param Product $product
     * @return $this
     */
    public function initInventoryForProduct(Product $product);

    /**
     * Get availability
     *
     * @return string
     */
    public function getAvailability();

    /**
     * Get inventory
     *
     * @return int
     */
    public function getInventory();
}
