<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

class Identifier implements \Magento\Framework\Data\OptionSourceInterface
{
    const PRODUCT_IDENTIFIER_SKU = 'sku';
    const PRODUCT_IDENTIFIER_ID = 'id';

    /**
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
