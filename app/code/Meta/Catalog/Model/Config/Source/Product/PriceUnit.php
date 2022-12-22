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

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class PriceUnit extends AbstractSource
{
    /**
     * {@inheritDoc}
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('Please Select')],
                ['value' => 'fl', 'label' => __('Fluid Ounce')],
                ['value' => 'gal', 'label' => __('Gallon')],
                ['value' => 'oz', 'label' => __('Ounce')],
                ['value' => 'pt', 'label' => __('Pint')],
                ['value' => 'qt', 'label' => __('Quart')],
                ['value' => 'cl', 'label' => __('Centilitre')],
                ['value' => 'cbm', 'label' => __('Cubic Meter')],
                ['value' => 'l', 'label' => __('Liter')],
                ['value' => 'ml', 'label' => __('Milliliter')],
                ['value' => 'cm', 'label' => __('Centimeter')],
                ['value' => 'ft', 'label' => __('Foot')],
                ['value' => 'in', 'label' => __('Inch')],
                ['value' => 'm', 'label' => __('Meter')],
                ['value' => 'yd', 'label' => __('Yard')],
                ['value' => 'sqft', 'label' => __('Square Foot')],
                ['value' => 'sqm', 'label' => __('Square Meter')],
                ['value' => 'ct', 'label' => __('Count')],
            ];
        }
        return $this->_options;
    }
}
