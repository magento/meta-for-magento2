<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Config extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'facebook_business_extension';

    protected function _construct()
    {
        $this->_init('Facebook\BusinessExtension\Model\ResourceModel\Config');
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getConfigKey()];
    }
}
