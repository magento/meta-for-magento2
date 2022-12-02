<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Assembly extends AbstractSource
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
                ['value' => 'yes', 'label' => __('Yes')],
                ['value' => 'no', 'label' => __('No')],
            ];
        }
        return $this->_options;
    }
}
