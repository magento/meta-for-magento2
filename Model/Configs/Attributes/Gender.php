<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Configs\Attributes;

class Gender extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
                ['value' => 'Male', 'label' => __('Male')],
                ['value' => 'Female', 'label' => __('Female')],
                ['value' => 'Unisex', 'label' => __('Unisex')],
            ];
        }
        return $this->_options;
    }
}
