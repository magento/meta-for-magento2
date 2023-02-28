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

namespace Meta\Sales\Api\Data;

interface FacebookOrderInterface
{
    /**
     * Get magento order id
     *
     * @return mixed
     */
    public function getMagentoOrderId();

    /**
     * Set Magento order id
     *
     * @param int $orderId
     * @return $this
     */
    public function setMagentoOrderId($orderId);

    /**
     * Get facebook order id
     *
     * @return mixed
     */
    public function getFacebookOrderId();

    /**
     * Set facebook order id
     *
     * @param int $orderId
     * @return $this
     */
    public function setFacebookOrderId($orderId);

    /**
     * Get channel
     *
     * @return mixed
     */
    public function getChannel();

    /**
     * Set channel
     *
     * @param mixed $channel
     * @return $this
     */
    public function setChannel($channel);

    /**
     * Get extra data
     *
     * @return mixed
     */
    public function getExtraData();

    /**
     * Set extra data
     *
     * @param array $extraData
     * @return $this
     */
    public function setExtraData(array $extraData);
}
