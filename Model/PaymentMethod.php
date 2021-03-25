<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class PaymentMethod extends AbstractMethod
{
    const METHOD_CODE = 'facebook';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
