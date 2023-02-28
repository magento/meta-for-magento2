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

namespace Meta\Promotions\Model\Promotion\Feed;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\OfflineShipping\Model\SalesRule\Rule as ShippingRule;

class Builder
{
    private const MIN_SUBTOTAL = '%02.2f %s';

    //https://developers.facebook.com/docs/marketing-api/catalog/guides/offers-api#available-fields
    private const ATTR_OFFER_ID = 'offer_id';
    private const ATTR_OFFER_TITLE = 'title';
    private const ATTR_OFFER_APPLICATION_TYPE = 'application_type';
    private const ATTR_PUBLIC_COUPON_CODE = 'public_coupon_code';
    private const ATTR_START_DATE = 'start_date_time';
    private const ATTR_END_DATE = 'end_date_time';
    private const ATTR_MIN_SUBTOTAL = 'min_subtotal';
    private const ATTR_MIN_QUANTITY = 'min_quantity';
    private const ATTR_TARGET_FILTER = 'target_filter';
    private const ATTR_REDEEM_LIMIT = 'redeem_limit_per_user';
    private const ATTR_VALUE_TYPE = 'value_type';
    private const ATTR_FIXED_AMOUNT_OFF = 'fixed_amount_off';
    private const ATTR_PERCENT_OFF = 'percent_off';
    private const ATTR_TARGET_GRANULARITY = 'target_granularity';
    private const ATTR_TARGET_TYPE = 'target_type';
    private const ATTR_TARGET_SHIPPING_OPTIONS = 'target_shipping_option_types';
    private const ATTR_TARGET_SELECTION = 'target_selection';
    private const ATTR_TARGET_QUANTITY = 'target_quantity';

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
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param RuleCollection $ruleCollection
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        FBEHelper      $fbeHelper,
        SystemConfig   $systemConfig,
        RuleCollection $ruleCollection,
        RuleFactory    $ruleFactory
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->ruleCollection = $ruleCollection;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Build promo
     *
     * @param Rule $rule
     * @return array|float[]|int[]|null[]|string[]
     * @throws LocalizedException
     */
    public function buildPromoEntry(Rule $rule)
    {
        $startDate = $rule->getFromDate() ? strtotime($rule->getFromDate()) : strtotime('now');
        $endDate = $rule->getToDate();
        if ($endDate) {
            $endDate = strtotime($endDate);
        }

        $entry = [
            self::ATTR_OFFER_ID => rand(),
            self::ATTR_OFFER_TITLE => $rule->getName(),
            self::ATTR_START_DATE => $startDate,
            self::ATTR_END_DATE => $endDate,
            self::ATTR_REDEEM_LIMIT => $rule->getUsesPerCustomer(),
        ];

        $entry = $this->applyCouponType($rule, $entry);

        $skus = $this->getMatchingSkus($rule);
        if (!empty($skus)) {
            $entry += [self::ATTR_TARGET_SELECTION => "SPECIFIC_PRODUCTS"];
            $entry += [self::ATTR_TARGET_FILTER => "{'retailer_id': {'is_any':" . json_encode($skus) . "}}"];
        } else {
            $entry += [self::ATTR_TARGET_SELECTION => "ALL_CATALOG_PRODUCTS"];
            $entry += [self::ATTR_TARGET_FILTER => null];
        }

        return $this->applyDiscountAction($rule, $entry);
    }

    /**
     * Apply Coupon Type
     *
     * @param Rule $rule
     * @param array $entry
     * @return array|string[]
     * @throws LocalizedException
     */
    private function applyCouponType(Rule $rule, array $entry): array
    {
        $couponType = $rule->getCouponType();
        switch ($couponType) {
            case Rule::COUPON_TYPE_NO_COUPON:
                $entry += [self::ATTR_PUBLIC_COUPON_CODE => ''];
                $entry += [self::ATTR_OFFER_APPLICATION_TYPE => 'AUTOMATIC_AT_CHECKOUT'];
                break;
            case Rule::COUPON_TYPE_SPECIFIC:
                $entry += [self::ATTR_PUBLIC_COUPON_CODE => $rule->getCouponCode()];
                $entry += [self::ATTR_OFFER_APPLICATION_TYPE => 'BUYER_APPLIED'];
                break;
            case Rule::COUPON_TYPE_AUTO:
            default:
                throw new LocalizedException(__(sprintf('Unsupported coupon type: %s ', $couponType)));
        }

        return $entry;
    }

    /**
     * Generic apply discount
     *
     * @param Rule $rule
     * @param array $entry
     * @return float[]|int[]|null[]|string[]
     * @throws LocalizedException
     */
    private function applyDiscountAction(Rule $rule, array $entry): array
    {
        $isShipping = $rule->getSimpleFreeShipping();
        switch ($isShipping) {
            case ShippingRule::FREE_SHIPPING_ADDRESS:
                $entry = $this->applyDiscountActionFreeShipping($rule, $entry);
                break;
            case ShippingRule::FREE_SHIPPING_ITEM:
                throw new LocalizedException(__(sprintf('Unsupported free shipping: %s', $isShipping)));
            default:
                $entry = $this->applyDiscountActionNonFreeShipping($rule, $entry);
        }
        return $entry;
    }

    /**
     * Apply discount with free shipping
     *
     * @param Rule $rule
     * @param array $entry
     * @return array|int[]|null[]|string[]
     */
    private function applyDiscountActionFreeShipping(Rule $rule, array $entry): array
    {
        $entry += [self::ATTR_MIN_SUBTOTAL => $this->getMinSubtotal($rule)];
        $entry += [self::ATTR_VALUE_TYPE => "PERCENTAGE"];
        $entry += [self::ATTR_FIXED_AMOUNT_OFF => ""];
        $entry += [self::ATTR_TARGET_GRANULARITY => "ITEM_LEVEL"];
        $entry += [self::ATTR_TARGET_TYPE => 'SHIPPING'];
        $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => "STANDARD"];
        $entry += [self::ATTR_MIN_QUANTITY => '0'];
        $entry += [self::ATTR_PERCENT_OFF => 100];
        $entry += [self::ATTR_TARGET_QUANTITY => ''];

        return $entry;
    }

    /**
     * Apply Discount without free shipping
     *
     * @param Rule $rule
     * @param array $entry
     * @return float[]|int[]|null[]|string[]
     * @throws LocalizedException
     */
    private function applyDiscountActionNonFreeShipping(Rule $rule, array $entry): array
    {
        $action = $rule->getSimpleAction();
        switch ($action) {
            case "by_percent":
                $entry = $this->applyDiscountActionByPercent($rule, $entry);
                break;
            case "by_fixed":
                $entry = $this->applyDiscountActionByFixed($rule, $entry);
                break;
            case "buy_x_get_y":
                $entry = $this->applyDiscountActionBuyXGetY($rule, $entry);
                break;
            case "cart_fixed":
            default:
                throw new LocalizedException(__(sprintf('Unsupported discount action: %s', $action)));
        }

        return $entry;
    }

    /**
     * Apply discount percentage
     *
     * @param Rule $rule
     * @param array $entry
     * @return array|null[]|string[]
     */
    private function applyDiscountActionByPercent(Rule $rule, array $entry): array
    {
        $entry += [self::ATTR_MIN_SUBTOTAL => $this->getMinSubtotal($rule)];
        $entry += [self::ATTR_VALUE_TYPE => "PERCENTAGE"];
        $entry += [self::ATTR_FIXED_AMOUNT_OFF => ""];
        $entry += [self::ATTR_TARGET_GRANULARITY => "ORDER_LEVEL"];
        $entry += [self::ATTR_TARGET_TYPE => 'LINE_ITEM'];
        $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => ''];
        $entry += [self::ATTR_MIN_QUANTITY => '0'];
        $entry += [self::ATTR_PERCENT_OFF => (int) $rule->getDiscountAmount()];
        $entry += [self::ATTR_TARGET_QUANTITY => ''];

        return $entry;
    }

    /**
     * Apply Fixed Discount
     *
     * @param Rule $rule
     * @param array $entry
     * @return array|string[]
     */
    private function applyDiscountActionByFixed(Rule $rule, array $entry): array
    {
        $entry += [self::ATTR_MIN_SUBTOTAL => ''];
        $entry += [self::ATTR_VALUE_TYPE => "FIXED_AMOUNT"];
        $entry += [self::ATTR_FIXED_AMOUNT_OFF => (int) $rule->getDiscountAmount()];
        $entry += [self::ATTR_TARGET_GRANULARITY => "ORDER_LEVEL"];
        $entry += [self::ATTR_TARGET_TYPE => 'LINE_ITEM'];
        $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => ''];
        $entry += [self::ATTR_MIN_QUANTITY => '0'];
        $entry += [self::ATTR_PERCENT_OFF => ""];
        $entry += [self::ATTR_TARGET_QUANTITY => ''];

        return $entry;
    }

    /**
     * Apply Discount Action for Buy X Get Y
     *
     * @param Rule $rule
     * @param array $entry
     * @return array|float[]|int[]|string[]
     */
    private function applyDiscountActionBuyXGetY(Rule $rule, array $entry): array
    {
        $entry += [self::ATTR_MIN_SUBTOTAL => ''];
        $entry += [self::ATTR_VALUE_TYPE => "PERCENTAGE"];
        $entry += [self::ATTR_FIXED_AMOUNT_OFF => ''];
        $entry += [self::ATTR_TARGET_GRANULARITY => "ITEM_LEVEL"];
        $entry += [self::ATTR_TARGET_TYPE => 'LINE_ITEM'];
        $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => ''];
        $entry += [self::ATTR_MIN_QUANTITY => $rule->getDiscountStep()];

        $discountAmt = $rule->getDiscountAmount();
        if ($discountAmt < 1) {
            $entry += [self::ATTR_PERCENT_OFF => $discountAmt * 100];
            $entry += [self::ATTR_TARGET_QUANTITY => 1];
        } else {
            $entry += [self::ATTR_PERCENT_OFF => "100"];
            $entry += [self::ATTR_TARGET_QUANTITY => (int) $discountAmt];
        }

        return $entry;
    }

    /**
     * Get minimum subtotal
     *
     * @param Rule $rule
     * @return string|null
     */
    private function getMinSubtotal(Rule $rule)
    {
        //this assumes the first condition is "If ANY of these conditions are TRUE"
        // or "If ALL of these conditions are TRUE"
        $rootCondition = $rule->getConditions();
        $conditions = null;
        if ($rootCondition) {
            $conditions = $rootCondition->getConditions();
        }
        //only supporting 1 condition
        if ($conditions && count($conditions) == 1) {
            $operator = $conditions[0]->getOperator() ?? '';
            $attribute = $conditions[0]->getAttribute() ?? '';
            if ($operator == '>=' && $attribute == 'base_subtotal') {
                return sprintf(self::MIN_SUBTOTAL, $conditions[0]->getValue(), $this->getStoreCurrency());
            }
        }
        return null;
    }

    /**
     * Get header fields
     *
     * @return array
     */
    public function getHeaderFields()
    {
        return [
            self::ATTR_OFFER_ID,
            self::ATTR_OFFER_TITLE,
            self::ATTR_START_DATE,
            self::ATTR_END_DATE,
            self::ATTR_REDEEM_LIMIT,
            self::ATTR_PUBLIC_COUPON_CODE,
            self::ATTR_OFFER_APPLICATION_TYPE,
            self::ATTR_TARGET_SELECTION,
            self::ATTR_TARGET_FILTER,
            self::ATTR_MIN_SUBTOTAL,
            self::ATTR_VALUE_TYPE,
            self::ATTR_FIXED_AMOUNT_OFF,
            self::ATTR_TARGET_GRANULARITY,
            self::ATTR_TARGET_TYPE,
            self::ATTR_TARGET_SHIPPING_OPTIONS,
            self::ATTR_MIN_QUANTITY,
            self::ATTR_PERCENT_OFF,
            self::ATTR_TARGET_QUANTITY
        ];
    }

    /**
     * Get match skus
     *
     * @param Rule $offer
     * @return array
     */
    private function getMatchingSkus($offer): array
    {
        //TODO this isn't a reliable way to get products that match for an offer
        $skus = [];
        $ruleData = $offer->getActionsSerialized();
        if ($ruleData) {
            $ruleDataArray = json_decode($ruleData, true);
            if (isset($ruleDataArray['conditions'])) {
                $conditions = $ruleDataArray['conditions'];
                foreach ($conditions as $condition) {
                    if ($condition['attribute'] === 'sku') {
                        $skuValues = $condition['value'];
                        $skuValues = explode(",", $skuValues);
                        foreach ($skuValues as $skuValue) {
                            $skus[] = $skuValue;
                        }
                    }
                }
            }
        }
        return $skus;
    }

    /**
     * Get store currency
     *
     * @return mixed
     */
    private function getStoreCurrency()
    {
        //TODO change this to take in current store id instead of default
        return $this->fbeHelper->getStore()->getCurrentCurrency()->getCode();
    }
}
