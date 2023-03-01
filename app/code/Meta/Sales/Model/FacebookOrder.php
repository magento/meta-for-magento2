<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\Sales\Model;

use Meta\Sales\Api\Data\FacebookOrderInterface;
use Meta\Sales\Model\ResourceModel\FacebookOrder as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class FacebookOrder extends AbstractModel implements FacebookOrderInterface
{
    public const STATE_CREATED = 'CREATED';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get magento order id
     *
     * @return array|mixed|null
     */
    public function getMagentoOrderId()
    {
        return $this->getData('magento_order_id');
    }

    /**
     * Set magento order id
     *
     * @param int $orderId
     * @return $this|FacebookOrder
     */
    public function setMagentoOrderId($orderId)
    {
        $this->setData('magento_order_id', $orderId);
        return $this;
    }

    /**
     * Get facebook order id
     *
     * @return mixed
     */
    public function getFacebookOrderId()
    {
        return $this->getData('facebook_order_id');
    }

    /**
     * Set facebook order id
     *
     * @param mixed $orderId
     * @return $this|FacebookOrder
     */
    public function setFacebookOrderId($orderId)
    {
        $this->setData('facebook_order_id', $orderId);
        return $this;
    }

    /**
     * Get channel
     *
     * @return array|mixed|null
     */
    public function getChannel()
    {
        return $this->getData('channel');
    }

    /**
     * Set channel
     *
     * @param mixed $channel
     * @return $this|FacebookOrder
     */
    public function setChannel($channel)
    {
        $this->setData('channel', $channel);
        return $this;
    }

    /**
     * Get extra data
     *
     * @return mixed
     */
    public function getExtraData()
    {
        return json_decode($this->getData('extra_data') ?? '', true);
    }

    /**
     * Set extra data
     *
     * @param array $extraData
     * @return $this|FacebookOrder
     */
    public function setExtraData(array $extraData)
    {
        $this->setData('extra_data', json_encode($extraData));
        return $this;
    }
}
