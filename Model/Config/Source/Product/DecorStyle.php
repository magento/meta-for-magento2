<?php

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class DecorStyle extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('Please Select')],
                ['value' => 'Bohemian', 'label' => __('Bohemian')],
                ['value' => 'Contemporary', 'label' => __('Contemporary')],
                ['value' => 'Industrial', 'label' => __('Industrial')],
                ['value' => 'Mid-Century', 'label' => __('Mid-Century')],
                ['value' => 'Modern', 'label' => __('Modern')],
                ['value' => 'Rustic', 'label' => __('Rustic')],
                ['value' => 'Vintage', 'label' => __('Vintage')],
            ];
        }
        return $this->_options;
    }
}
