<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class AgeGroup extends AbstractSource
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
                ['value' => 'adult', 'label' => __('adult')],
                ['value' => 'all ages', 'label' => __('all ages')],
                ['value' => 'teen', 'label' => __('teen')],
                ['value' => 'kids', 'label' => __('kids')],
                ['value' => 'toddler', 'label' => __('toddler')],
                ['value' => 'infant', 'label' => __('infant')],
                ['value' => 'newborn', 'label' => __('newborn')],
            ];
        }
        return $this->_options;
    }
}
