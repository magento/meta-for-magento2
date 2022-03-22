<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DefaultOrderStatus implements OptionSourceInterface
{
    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_PROCESSING = 'processing';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ORDER_STATUS_PENDING, 'label' => __('Pending')],
            ['value' => self::ORDER_STATUS_PROCESSING, 'label' => __('Processing')],
        ];
    }
}
