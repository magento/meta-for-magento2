<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
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

    /**
     * Disallow this method in Magento checkout
     * https://devdocs.magento.com/guides/v2.4/payments-integrations/base-integration/payment-option-config.html
     *
     * @var bool
     */
    protected $_canUseCheckout = false;
}
