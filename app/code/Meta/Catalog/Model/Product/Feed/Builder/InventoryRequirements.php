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

namespace Meta\Catalog\Model\Product\Feed\Builder;

use Magento\Catalog\Model\Product;

abstract class InventoryRequirements implements InventoryInterface
{
    /**
     * Validates if the item meets inventory requirements to be flagged as in stock.
     *
     * This will pass if either the item does not require inventory, or the available inventory is greater than zero.
     *
     * @param ?Product $product
     * @return bool
     */
    public function meetsInventoryRequirementsToBeInStock(?Product $product): bool
    {
        if (!$product) {
            return false;
        }

        if (!$this->isInventoryRequired($product)) {
            return true;
        }

        return $this->getInventory() > 0;
    }

    /**
     * Identifies if inventory is applicable and should be checked as part of generating item availability.
     * For some aggregate product types, such as grouped products and bundles, inventory isn't applicable.
     * Therefore, for these types of products, we should not incorporate inventory into stock status checks
     *
     * @param Product $product
     * @return bool
     */
    private function isInventoryRequired(Product $product): bool
    {
        switch ($product->getTypeId()) {
            case 'bundle':
            case 'grouped':
                return false;
            default:
                return true;
        }
    }
}
