<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

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
