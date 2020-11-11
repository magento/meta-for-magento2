<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Configs\Attributes;

class Pattern extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
                ['value' => 'Plaid', 'label' => __('Plaid')],
                ['value' => 'Polka', 'label' => __('Polka')],
                ['value' => 'Dot', 'label' => __('Dot')],
                ['value' => 'Gingham', 'label' => __('Gingham')],
                ['value' => 'Chevron', 'label' => __('Chevron')],
            ];
        }
        return $this->_options;
    }
}
