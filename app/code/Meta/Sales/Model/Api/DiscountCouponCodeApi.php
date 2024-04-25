<?php

declare(strict_types=1);

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

namespace Meta\Sales\Model\Api;

use Meta\Sales\Api\DiscountCouponCodeApiInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Coupon\MassgeneratorFactory;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;

/**
 * Class for generating a discount coupon code based on a rule ID.
 */
class DiscountCouponCodeApi implements DiscountCouponCodeApiInterface
{
    /**
     * @var RuleFactory Factory for creating sales rule instances.
     */
    private RuleFactory $ruleFactory;

    /**
     * @var MassgeneratorFactory Factory for creating mass coupon code generator instances.
     */
    private MassgeneratorFactory $massGeneratorFactory;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * Constructor for DiscountCouponCodeApi.
     *
     * @param RuleFactory $ruleFactory Factory for creating sales rule instances.
     * @param MassgeneratorFactory $massGeneratorFactory Factory for creating mass coupon code generator instances.
     * @param Authenticator $authenticator
     */
    public function __construct(
        RuleFactory          $ruleFactory,
        MassgeneratorFactory $massGeneratorFactory,
        Authenticator        $authenticator
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->massGeneratorFactory = $massGeneratorFactory;
        $this->authenticator = $authenticator;
    }

    /**
     * Generate a coupon code based on a rule ID.
     *
     * @param int $ruleId The ID of the rule to generate the coupon for.
     * @return string The generated coupon code.
     * @throws LocalizedException If the rule does not exist or coupon generation fails.
     */
    public function generateCouponCode(int $ruleId): string
    {
        $this->authenticator->authenticateRequest();

        $rule = $this->ruleFactory->create()->load($ruleId);
        if (!$rule->getId()) {
            throw new LocalizedException(__('The specified discount rule does not exist.'));
        }

        $generator = $this->massGeneratorFactory->create();
        $generator->setFormat(\Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC);
        $generator->setRuleId($ruleId);
        $generator->setUsesPerCoupon(1);
        $generator->setDash(3);
        $generator->setLength(9);
        $generator->setPrefix('META_');
        $generator->setSuffix('');
        $generator->setQty(1);

        $generator->generatePool();
        $coupons = $generator->getGeneratedCodes();

        if (empty($coupons)) {
            throw new LocalizedException(__('Failed to generate coupon code.'));
        }

        return $coupons[0];
    }
}
