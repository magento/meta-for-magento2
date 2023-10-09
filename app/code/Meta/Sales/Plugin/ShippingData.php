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

namespace Meta\Sales\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CollectionFactory;

class ShippingData
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $tableRateCollection;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var int
     */
    private int $storeId;

    public const ATTR_ENABLED = 'enabled';
    public const ATTR_TITLE = 'title';
    public const ATTR_METHOD_NAME = 'method_name';
    public const ATTR_SHIPPING_METHODS = 'shipping_methods';
    public const ATTR_HANDLING_FEE = 'handling_fee';
    public const ATTR_HANDLING_FEE_TYPE = 'handling_fee_type';
    public const ATTR_SHIPPING_FEE_TYPE = 'shipping_fee_type';
    public const ATTR_FREE_SHIPPING_MIN_ORDER_AMOUNT = 'free_shipping_minimum_order_amount';

    public const EXTERNAL_REFERENCE_ID = 'external_reference_id';

    /**
     * @param CollectionFactory $tableRateCollection
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CollectionFactory    $tableRateCollection,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->tableRateCollection = $tableRateCollection;
    }

    /**
     * Setter for store ID
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
     * Returns shipping profiles based on the AbstractModel carrier passed in
     *
     * @param string $shippingProfileType
     * @return array
     */
    public function buildShippingProfile(string $shippingProfileType): array
    {
        $isEnabled = $this->getFieldFromModel($shippingProfileType, 'active');
        $title = $this->getFieldFromModel($shippingProfileType, 'title');
        $methodName = $this->getFieldFromModel($shippingProfileType, 'name');
        $price = (float)$this->getFieldFromModel($shippingProfileType, 'price') ?? 0.0;
        $shippingType = $this->getFieldFromModel($shippingProfileType, 'type');
        // As per adobe docs, when shipping type is none, and shipping profile type is flatRate, shipping is free
        if ($shippingType === null && $shippingProfileType === ShippingProfileTypes::FLAT_RATE) {
            $price = 0.0;
        }
        $allowSpecificCountrySelection = $this->getFieldFromModel($shippingProfileType, 'sallowspecific');
        if ($allowSpecificCountrySelection) {
            $allowedCountries = $this->getFieldFromModel($shippingProfileType, 'specificcountry');
        } else {
            $allowedCountries = "*";
        }
        if ($shippingProfileType === ShippingProfileTypes::TABLE_RATE) {
            $shippingMethods = $this->getShippingMethodsInfoForTableRates();
        } else {
            $shippingMethods = $this->buildShippingMethodsInfo($allowedCountries, $price);
        }
        $freeShippingThreshold = $this->getFieldFromModel($shippingProfileType, 'free_shipping_subtotal');
        $handlingFee = $this->getFieldFromModel($shippingProfileType, 'handling_fee');
        $handlingFeeType = $this->getFieldFromModel($shippingProfileType, 'handling_type');
        $externalReferenceId = $this->getExternalReferenceID($shippingProfileType);
        return [
            self::ATTR_ENABLED => $isEnabled,
            self::ATTR_TITLE => $title,
            self::ATTR_METHOD_NAME => $methodName,
            self::ATTR_SHIPPING_METHODS => json_encode($shippingMethods),
            self::ATTR_HANDLING_FEE => $handlingFee,
            self::ATTR_HANDLING_FEE_TYPE => $handlingFeeType,
            self::ATTR_SHIPPING_FEE_TYPE => $shippingType,
            self::ATTR_FREE_SHIPPING_MIN_ORDER_AMOUNT => $freeShippingThreshold,
            self::EXTERNAL_REFERENCE_ID => $externalReferenceId
        ];
    }

    /**
     * Get field from abstract carrier DB
     *
     * @param string $shippingProfileType
     * @param string $field
     * @return mixed
     */
    private function getFieldFromModel(string $shippingProfileType, string $field)
    {
        $path = 'carriers/' . $shippingProfileType . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    /**
     * Get external reference id for shipping settings which meta will use to identify the selected shipping option
     *
     * @param string $shippingProfileType
     * @return string
     */
    private function getExternalReferenceID(string $shippingProfileType): string
    {
        switch ($shippingProfileType) {
            case ShippingProfileTypes::TABLE_RATE:
                return ShippingMethodTypes::TABLE_RATE;
            case ShippingProfileTypes::FLAT_RATE:
                return ShippingMethodTypes::FLAT_RATE;
            case ShippingProfileTypes::FREE_SHIPPING:
                return ShippingMethodTypes::FREE_SHIPPING;
            default:
                return "";
        }
    }

    /**
     * A function that builds shipping methods info for shipping profiles which are not table rates
     *
     * @param string $allowedCountries
     * @param float $price
     * @return array
     */
    private function buildShippingMethodsInfo(string $allowedCountries, float $price): array
    {
        $result = [];
        $allowedCountries = explode(",", $allowedCountries);
        foreach ($allowedCountries as $country) {
            $result[] = [
                'price' => $price,
                'country' => $country,
                'state' => '*',
                'zip' => '*'
            ];
        }
        return $result;
    }

    /**
     * Returns shipping methods for table rate settings as specified in the Adobe API documentation
     *
     * Link: https://experienceleague.adobe.com/docs/commerce-admin/stores-sales/delivery/basic-methods/shipping-table-rate.html
     *
     * @return array
     */
    protected function getShippingMethodsInfoForTableRates(): array
    {
        $shippingMethodsInfo = [];
        $collection = $this->tableRateCollection->create();
        $collection->getSelect()
            ->joinLeft(
                ['region' => $collection->getTable('directory_country_region')],
                'main_table.dest_region_id = region.region_id',
                ['region.code AS region_code']
            );
        foreach ($collection as $rate) {
            $rate->getName();
            // Determine the condition type (weight, price, or number of items)
            $conditionType = null;
            $conditionValue = $rate->getConditionValue();
            $conditionName = $rate->getConditionName();
            if ($conditionName === 'package_weight') {
                $conditionType = 'weight';
            } elseif ($conditionName === 'package_value_with_discount') {
                $conditionType = 'price';
            } elseif ($conditionName === 'package_qty') {
                $conditionType = 'item_qty';
            }
            $shippingMethodsInfo[] = [
                'price' => $rate->getPrice(),
                'country' => $rate->getDestCountryId(),
                'state' => $rate->getRegionCode(),
                'zip' => $rate->getDestZip(),
                'condition' => [
                    'type' => $conditionType,
                    'amount' => $conditionValue,
                ],
            ];
        }
        return $shippingMethodsInfo;
    }
}
