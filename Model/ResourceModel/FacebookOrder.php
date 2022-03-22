<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FacebookOrder extends AbstractDb
{
    const TABLE_NAME_ORDER = 'facebook_sales_order';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME_ORDER, 'entity_id');
    }
}
