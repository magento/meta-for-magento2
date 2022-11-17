<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Api\Data;

interface FacebookOrderInterface
{
    /**
     * @return mixed
     */
    public function getMagentoOrderId();

    /**
     * @param $orderId
     * @return $this
     */
    public function setMagentoOrderId($orderId);

    /**
     * @return mixed
     */
    public function getFacebookOrderId();

    /**
     * @param $orderId
     * @return $this
     */
    public function setFacebookOrderId($orderId);

    /**
     * @return mixed
     */
    public function getChannel();

    /**
     * @param $channel
     * @return $this
     */
    public function setChannel($channel);

    /**
     * @return mixed
     */
    public function getExtraData();

    /**
     * @param array $extraData
     * @return $this
     */
    public function setExtraData(array $extraData);
}
