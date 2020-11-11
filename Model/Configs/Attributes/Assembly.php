<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Configs\Attributes;

class Assembly extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
