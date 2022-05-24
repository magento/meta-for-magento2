<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Promotion\Feed;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Api\Data\ConditionInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\OfflineShipping\Model\SalesRule\Rule as ShippingRule;

class Builder
{
    //todo convert for other currencies
    const MIN_SUBTOTAL = '%02.2f %s';

    //https://developers.facebook.com/docs/marketing-api/catalog/guides/offers-api#available-fields
    const ATTR_OFFER_ID = 'offer_id';
    const ATTR_OFFER_TITLE = 'title';
    const ATTR_OFFER_APPLICATION_TYPE = 'application_type';
    const ATTR_PUBLIC_COUPON_CODE = 'public_coupon_code';
    const ATTR_START_DATE = 'start_date_time';
    const ATTR_END_DATE = 'end_date_time';
    const ATTR_MIN_SUBTOTAL = 'min_subtotal';
    const ATTR_MIN_QUANTITY = 'min_quantity';

    const ATTR_REDEEM_LIMIT = 'redeem_limit_per_user';
    const ATTR_VALUE_TYPE = 'value_type';
    const ATTR_FIXED_AMOUNT_OFF = 'fixed_amount_off';
    const ATTR_PERCENT_OFF = 'percent_off';
    const ATTR_TARGET_GRANULARITY = 'target_granularity';
    const ATTR_TARGET_TYPE = 'target_type';
    const ATTR_TARGET_SHIPPING_OPTIONS = 'target_shipping_option_types';
    const ATTR_TARGET_SELECTION = 'target_selection';
    const ATTR_TARGET_QUANTITY = 'target_quantity';

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;


    protected $storeId;

    protected $ruleCollection;

    protected $ruleFactory;


    public function __construct(
        FBEHelper      $fbeHelper,
        SystemConfig   $systemConfig,
        RuleCollection $ruleCollection,
        RuleFactory    $ruleFactory
    )
    {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->ruleCollection = $ruleCollection;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    public function buildPromoEntry(Rule $rule)
    {
        $startDate = $rule->getFromDate() ? strtotime($rule->getFromDate()) : strtotime('now');
        $endDate = $rule->getToDate();
        if ($endDate) {
            $endDate = strtotime($endDate);
        }
        $isShipping = $rule->getSimpleFreeShipping();

        $entry = [
            self::ATTR_OFFER_ID => rand(),
            self::ATTR_OFFER_TITLE => $rule->getName(),
            self::ATTR_START_DATE => $startDate,
            self::ATTR_END_DATE => $endDate,
            self::ATTR_REDEEM_LIMIT => $rule->getUsesPerCustomer(),
        ];
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
        $action = $rule->getSimpleAction();
        switch ($isShipping) {
            case ShippingRule::FREE_SHIPPING_ADDRESS:
                $entry += [self::ATTR_MIN_SUBTOTAL => $this->getMinSubtotal($rule)];
                $entry += [self::ATTR_VALUE_TYPE => "PERCENTAGE"];
                $entry += [self::ATTR_FIXED_AMOUNT_OFF => ""];
                $entry += [self::ATTR_PERCENT_OFF => 100];
                $entry += [self::ATTR_TARGET_GRANULARITY => "ITEM_LEVEL"];
                $entry += [self::ATTR_TARGET_SELECTION => "ALL_CATALOG_PRODUCTS"];
                $entry += [self::ATTR_TARGET_TYPE => 'SHIPPING'];
                $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => "STANDARD"];
                $entry += [self::ATTR_MIN_QUANTITY => '0'];
                $entry += [self::ATTR_TARGET_QUANTITY => ''];
                break;
            case ShippingRule::FREE_SHIPPING_ITEM:
                throw new LocalizedException(__(sprintf('Unsupported free shipping: %s', $isShipping)));
            default:
                switch ($action) {
                    case "by_percent":
                        $entry += [self::ATTR_MIN_SUBTOTAL => $this->getMinSubtotal($rule)];
                        $entry += [self::ATTR_VALUE_TYPE => "PERCENTAGE"];
                        $entry += [self::ATTR_FIXED_AMOUNT_OFF => ""];
                        $entry += [self::ATTR_PERCENT_OFF => intval($rule->getDiscountAmount())];
                        $entry += [self::ATTR_TARGET_GRANULARITY => "ORDER_LEVEL"];
                        $entry += [self::ATTR_TARGET_SELECTION => "ALL_CATALOG_PRODUCTS"];
                        $entry += [self::ATTR_TARGET_TYPE => 'LINE_ITEM'];
                        $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => ''];
                        $entry += [self::ATTR_MIN_QUANTITY => '0'];
                        $entry += [self::ATTR_TARGET_QUANTITY => ''];
                        break;
                    case "by_fixed":
                        $entry += [self::ATTR_MIN_SUBTOTAL => ''];
                        $entry += [self::ATTR_VALUE_TYPE => "FIXED_AMOUNT"];
                        $entry += [self::ATTR_FIXED_AMOUNT_OFF => intval($rule->getDiscountAmount())];
                        $entry += [self::ATTR_PERCENT_OFF => ""];
                        $entry += [self::ATTR_TARGET_GRANULARITY => "ORDER_LEVEL"];
                        $entry += [self::ATTR_TARGET_SELECTION => "ALL_CATALOG_PRODUCTS"];
                        $entry += [self::ATTR_TARGET_TYPE => 'LINE_ITEM'];
                        $entry += [self::ATTR_TARGET_SHIPPING_OPTIONS => ''];
                        $entry += [self::ATTR_MIN_QUANTITY => '0'];
                        $entry += [self::ATTR_TARGET_QUANTITY => ''];
                        break;
                    case "buy_x_get_y":
                    case "cart_fixed":
                    default:
                        throw new LocalizedException(__(sprintf('Unsupported discount action: %s', $action)));
                }
        }
        return $entry;
    }

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
        throw new LocalizedException(__('Unsupported conditions'));
    }

    /**
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
            self::ATTR_MIN_SUBTOTAL,
            self::ATTR_VALUE_TYPE,
            self::ATTR_FIXED_AMOUNT_OFF,
            self::ATTR_PERCENT_OFF,
            self::ATTR_TARGET_GRANULARITY,
            self::ATTR_TARGET_SELECTION,
            self::ATTR_TARGET_TYPE,
            self::ATTR_TARGET_SHIPPING_OPTIONS,
            self::ATTR_MIN_QUANTITY,
            self::ATTR_TARGET_QUANTITY,
        ];
    }

    private function getStoreCurrency()
    {
        //TODO change this to take in current store id instead of default
        return $this->fbeHelper->getStore()->getCurrentCurrency()->getCode();
    }
}
