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

use Magento\Payment\Model\Method\AbstractMethod;

class PaymentMethod extends AbstractMethod
{
    public const METHOD_CODE = 'facebook';

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
