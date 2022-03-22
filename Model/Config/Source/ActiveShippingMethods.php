<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source;

use Magento\Shipping\Model\Config\Source\Allmethods;

class ActiveShippingMethods extends Allmethods
{
    /**
     * @inheritDoc
     */
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        return parent::toOptionArray(true);
    }
}
