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

namespace Meta\Catalog\Model\Config\Source\Product;

use Magento\Framework\Data\OptionSourceInterface;

class Identifier implements OptionSourceInterface
{
    public const PRODUCT_IDENTIFIER_SKU = 'sku';
    public const PRODUCT_IDENTIFIER_ID = 'id';

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::PRODUCT_IDENTIFIER_SKU, 'label' => __('SKU')],
            ['value' => self::PRODUCT_IDENTIFIER_ID, 'label' => __('Magento ID')],
        ];
    }
}
