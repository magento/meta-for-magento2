<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Source;

class FeedUploadMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    const UPLOAD_METHOD_FEED_API = 'feed_api';
    const UPLOAD_METHOD_CATALOG_BATCH_API = 'catalog_batch_api';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::UPLOAD_METHOD_FEED_API, 'label' => __('Feed API')],
            ['value' => self::UPLOAD_METHOD_CATALOG_BATCH_API, 'label' => __('Catalog Batch API')],
        ];
    }
}
