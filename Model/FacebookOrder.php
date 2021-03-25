<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model;

use Facebook\BusinessExtension\Api\Data\FacebookOrderInterface;
use Facebook\BusinessExtension\Model\ResourceModel\FacebookOrder as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class FacebookOrder extends AbstractModel implements FacebookOrderInterface
{
    const STATE_CREATED = 'CREATED';

    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    public function getMagentoOrderId()
    {
        return $this->getData('magento_order_id');
    }

    public function setMagentoOrderId($orderId)
    {
        $this->setData('magento_order_id', $orderId);
        return $this;
    }

    public function getFacebookOrderId()
    {
        return $this->getData('facebook_order_id');
    }

    public function setFacebookOrderId($orderId)
    {
        $this->setData('facebook_order_id', $orderId);
        return $this;
    }

    public function getChannel()
    {
        return $this->getData('channel');
    }

    public function setChannel($channel)
    {
        $this->setData('channel', $channel);
        return $this;
    }

    public function getExtraData()
    {
        return json_decode($this->getData('extra_data'), true);
    }

    public function setExtraData(array $extraData)
    {
        $this->setData('extra_data', json_encode($extraData));
        return $this;
    }
}
