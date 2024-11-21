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

namespace Meta\Promotions\Model\Promotion\Feed;

use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\OfflineShipping\Model\SalesRule\Rule as ShippingRule;

class Builder
{
    private const ATTR_RULE_ID = 'rule_id';
    private const ATTR_NAME = 'name';
    private const ATTR_DESCRIPTION = 'description';
    private const ATTR_FROM_DATE = 'from_date';
    private const ATTR_TO_DATE = 'to_date';
    private const ATTR_USES_PER_CUSTOMER = 'uses_per_customer';
    private const ATTR_IS_ACTIVE = 'is_active';
    private const ATTR_CONDITION = 'condition';
    private const ATTR_ACTION_CONDITION = 'action_condition';
    private const ATTR_STOP_RULES_PROCESSING = 'stop_rules_processing';
    private const ATTR_IS_ADVANCED = 'is_advanced';
    private const ATTR_PRODUCT_IDS = 'product_ids';
    private const ATTR_SORT_ORDER = 'sort_order';
    private const ATTR_SIMPLE_ACTION = 'simple_action';
    private const ATTR_DISCOUNT_AMOUNT = 'discount_amount';
    private const ATTR_DISCOUNT_QTY = 'discount_qty';
    private const ATTR_DISCOUNT_STEP = 'discount_step';
    private const ATTR_APPLY_TO_SHIPPING = 'apply_to_shipping';
    private const ATTR_TIMES_USED = 'times_used';
    private const ATTR_IS_RSS = 'is_rss';
    private const ATTR_COUPON_TYPE = 'coupon_type';
    private const ATTR_USE_AUTO_GENERATION = 'use_auto_generation';
    private const ATTR_USES_PER_COUPON = 'uses_per_coupon';
    private const ATTR_PRIMARY_COUPON = 'primary_coupon';
    private const ATTR_COUPONS = 'coupons';
    private const ATTR_SIMPLE_FREE_SHIPPING = 'simple_free_shipping';
    private const ATTR_STORE_LABELS = 'store_labels';
    private const ATTR_WEBSITE_IDS = 'website_ids';
    private const ATTR_CUSTOMER_GROUP_IDS = 'customer_group_ids';

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var RuleCollection
     */
    private $ruleCollection;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var CollectionFactory
     */
    protected $couponFactory;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param RuleCollection $ruleCollection
     * @param RuleFactory $ruleFactory
     * @param CollectionFactory $couponFactory
     */
    public function __construct(
        FBEHelper         $fbeHelper,
        SystemConfig      $systemConfig,
        RuleCollection    $ruleCollection,
        RuleFactory       $ruleFactory,
        CollectionFactory $couponFactory
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->ruleCollection = $ruleCollection;
        $this->ruleFactory = $ruleFactory;
        $this->couponFactory = $couponFactory;
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId): self
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Build promo
     *
     * @param Rule $rule
     * @return array
     */
    public function buildPromoEntry(Rule $rule): array
    {
        $fromDate = $rule->getFromDate() ? strtotime($rule->getFromDate()) : null;
        $toDate = $rule->getToDate() ? strtotime($rule->getToDate()) : null;

        $isActive = $this->boolFlagToString((int)$rule->getIsActive());
        $stopRulesProcessing = $this->boolFlagToString((int)$rule->getStopRulesProcessing());
        $isAdvanced = $this->boolFlagToString((int)$rule->getIsAdvanced());
        $applyToShipping = $this->boolFlagToString((int)$rule->getApplyToShipping());
        $isRss = $this->boolFlagToString((int)$rule->getIsRss());
        $useAutoGeneration = $this->boolFlagToString((int)$rule->getUseAutoGeneration());

        return [
            self::ATTR_RULE_ID => $rule->getRuleId(),
            self::ATTR_NAME => $rule->getName(),
            self::ATTR_DESCRIPTION => $rule->getDescription(),
            self::ATTR_FROM_DATE => $fromDate,
            self::ATTR_TO_DATE => $toDate,
            self::ATTR_USES_PER_CUSTOMER => $rule->getUsesPerCustomer(),
            self::ATTR_IS_ACTIVE => $isActive,
            self::ATTR_CONDITION => $rule->getConditionsSerialized(),
            self::ATTR_ACTION_CONDITION => $rule->getActionsSerialized(),
            self::ATTR_STOP_RULES_PROCESSING => $stopRulesProcessing,
            self::ATTR_IS_ADVANCED => $isAdvanced,
            self::ATTR_PRODUCT_IDS => $rule->getProductIds(),
            self::ATTR_SORT_ORDER => $rule->getSortOrder(),
            self::ATTR_SIMPLE_ACTION => $rule->getSimpleAction(),
            self::ATTR_DISCOUNT_AMOUNT => $rule->getDiscountAmount(),
            self::ATTR_DISCOUNT_QTY => $rule->getDiscountQty(),
            self::ATTR_DISCOUNT_STEP => $rule->getDiscountStep(),
            self::ATTR_APPLY_TO_SHIPPING => $applyToShipping,
            self::ATTR_TIMES_USED => $rule->getTimesUsed(),
            self::ATTR_IS_RSS => $isRss,
            self::ATTR_COUPON_TYPE => $this->getCouponTypeAsString($rule),
            self::ATTR_USE_AUTO_GENERATION => $useAutoGeneration,
            self::ATTR_USES_PER_COUPON => $rule->getUsesPerCoupon(),
            self::ATTR_PRIMARY_COUPON => $rule->getCouponCode(),
            self::ATTR_COUPONS => $this->getFirst10CouponsSerialized($rule),
            self::ATTR_SIMPLE_FREE_SHIPPING => $this->getSimpleFreeShippingAsString($rule),
            self::ATTR_STORE_LABELS => json_encode($rule->getStoreLabels()),
            self::ATTR_WEBSITE_IDS => json_encode($rule->getWebsiteIds()),
            self::ATTR_CUSTOMER_GROUP_IDS => json_encode($rule->getCustomerGroupIds()),
        ];
    }

    /**
     * Convert bool flag to string
     *
     * @param int $flag
     * @return string
     */
    private function boolFlagToString(int $flag): string
    {
        return $flag ? 'true' : 'false';
    }

    /**
     * Get coupon type string
     *
     * @param Rule $rule
     * @return string
     */
    private function getCouponTypeAsString(Rule $rule): string
    {
        $couponType = $rule->getCouponType();
        switch ($couponType) {
            case Rule::COUPON_TYPE_NO_COUPON:
                return 'coupon_type_no_coupon';
            case Rule::COUPON_TYPE_SPECIFIC:
                return 'coupon_type_specific';
            case Rule::COUPON_TYPE_AUTO:
                return 'coupon_type_auto';
            default:
                return 'coupon_type_unknown';
        }
    }

    /**
     * Get simple free shipping string
     *
     * @param Rule $rule
     * @return string
     */
    private function getSimpleFreeShippingAsString(Rule $rule): string
    {
        $simple_free_shipping = $rule->getSimpleFreeShipping();
        switch ($simple_free_shipping) {
            case ShippingRule::FREE_SHIPPING_ITEM:
                return 'free_shipping_item';
            case ShippingRule::FREE_SHIPPING_ADDRESS:
                return 'free_shipping_address';
            default:
                return 'no_free_shipping';
        }
    }

    /**
     * Get coupon creation type string
     *
     * @param Coupon $coupon
     * @return string
     */
    private function getCouponCreationTypeAsString(Coupon $coupon): string
    {
        $type = $coupon->getType();
        switch ($type) {
            case CouponInterface::TYPE_MANUAL:
                return 'type_manual';
            case CouponInterface::TYPE_GENERATED:
                return 'type_generated';
            default:
                return 'type_unknown';
        }
    }

    /**
     * Get first 10 coupons
     *
     * @param Rule $rule
     * @return string
     */
    private function getFirst10CouponsSerialized(Rule $rule): string
    {
        // Query for all generated coupons
        $coupons = $this->couponFactory->create()->addRuleToFilter($rule)->addGeneratedCouponsFilter()->getItems();

        // Only take the first 10 (for perf reasons)
        $coupons = array_slice($coupons, 0, 10);

        // Add the discount's primary coupon to the front of the list
        array_unshift($coupons, $rule->getPrimaryCoupon());

        $coupons = array_map(
            function (Coupon $coupon): array {
                return [
                    'id' => $coupon->getCouponId(),
                    'code' => $coupon->getCode(),
                    'usage_limit' => $coupon->getUsageLimit(),
                    'usage_per_customer' => $coupon->getUsagePerCustomer(),
                    'times_used' => $coupon->getTimesUsed(),
                    'is_primary' => $this->boolFlagToString((int)$coupon->getIsPrimary()),
                    'created_at' => $coupon->getCreatedAt(),
                    'type' => $this->getCouponCreationTypeAsString($coupon),
                    'extension_attributes' => $coupon->getExtensionAttributes()
                ];
            },
            $coupons
        );
        return json_encode($coupons);
    }

    /**
     * Get header fields
     *
     * @return array
     */
    public function getHeaderFields(): array
    {
        return [
            self::ATTR_RULE_ID,
            self::ATTR_NAME,
            self::ATTR_DESCRIPTION,
            self::ATTR_FROM_DATE,
            self::ATTR_TO_DATE,
            self::ATTR_USES_PER_CUSTOMER,
            self::ATTR_IS_ACTIVE,
            self::ATTR_CONDITION,
            self::ATTR_ACTION_CONDITION,
            self::ATTR_STOP_RULES_PROCESSING,
            self::ATTR_IS_ADVANCED,
            self::ATTR_PRODUCT_IDS,
            self::ATTR_SORT_ORDER,
            self::ATTR_SIMPLE_ACTION,
            self::ATTR_DISCOUNT_AMOUNT,
            self::ATTR_DISCOUNT_QTY,
            self::ATTR_DISCOUNT_STEP,
            self::ATTR_APPLY_TO_SHIPPING,
            self::ATTR_TIMES_USED,
            self::ATTR_IS_RSS,
            self::ATTR_COUPON_TYPE,
            self::ATTR_USE_AUTO_GENERATION,
            self::ATTR_USES_PER_COUPON,
            self::ATTR_PRIMARY_COUPON,
            self::ATTR_COUPONS,
            self::ATTR_SIMPLE_FREE_SHIPPING,
            self::ATTR_STORE_LABELS,
            self::ATTR_WEBSITE_IDS,
            self::ATTR_CUSTOMER_GROUP_IDS,
        ];
    }
}
