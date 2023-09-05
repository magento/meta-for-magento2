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
    protected CollectionFactory $tableRateCollection;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var string
     */
    private string $storeId;

    public const ATTR_ENABLED = 'enabled';
    public const ATTR_TITLE = 'title';
    public const ATTR_METHOD_NAME = 'method_name';
    public const ATTR_SHIPPING_METHODS = 'shipping_methods';
    public const ATTR_HANDLING_FEE = 'handling_fee';
    public const ATTR_HANDLING_FEE_TYPE = 'handling_fee_type';
    public const ATTR_SHIPPING_FEE_TYPE = 'shipping_fee_type';
    public const ATTR_FREE_SHIPPING_MIN_ORDER_AMOUNT = 'free_shipping_minimum_order_amount';

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
     * Setter for store id
     *
     * @param string $store_id
     * @return void
     */
    public function setStoreId(string $store_id)
    {
        $this->storeId = $store_id;
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
        $allowedCountries = $this->getFieldFromModel($shippingProfileType, 'specificcountries');
        if ($shippingProfileType === ShippingProfileTypes::TABLE_RATE) {
            $shippingMethods = $this->getShippingMethodsInfoForTableRates();
        } else {
            $shippingMethods = $this->buildShippingMethodsInfo($allowedCountries, $price);
        }
        $freeShippingThreshold = $this->getFieldFromModel($shippingProfileType, 'free_shipping_subtotal');
        $handlingFee = $this->getFieldFromModel($shippingProfileType, 'handling_fee');
        $handlingFeeType = $this->getFieldFromModel($shippingProfileType, 'handling_type');
        $shippingType = $this->getFieldFromModel($shippingProfileType, 'type');
        return [
            self::ATTR_ENABLED => $isEnabled,
            self::ATTR_TITLE => $title,
            self::ATTR_METHOD_NAME => $methodName,
            self::ATTR_SHIPPING_METHODS => json_encode($shippingMethods),
            self::ATTR_HANDLING_FEE => $handlingFee,
            self::ATTR_HANDLING_FEE_TYPE => $handlingFeeType,
            self::ATTR_SHIPPING_FEE_TYPE => $shippingType,
            self::ATTR_FREE_SHIPPING_MIN_ORDER_AMOUNT => $freeShippingThreshold,
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
     * A function that builds shipping methods info for shipping profiles which are not table rates
     *
     * @param string|null $allowedCountries
     * @param float $price
     * @return array
     */
    private function buildShippingMethodsInfo(?string $allowedCountries, float $price): array
    {
        $result = [];
        if ($allowedCountries === null) {
            $result[] = ['price' => $price,
                'country' => '*',
                'state' => '*',
                'zip' => '*'
            ];
            return $result;
        }
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
     *  Returns shipping methods for table rate settings as specified here: https://experienceleague.adobe.com/docs/commerce-admin/stores-sales/delivery/basic-methods/shipping-table-rate.html?lang=en
     *
     * @return array
     */
    protected function getShippingMethodsInfoForTableRates(): array
    {
        $shippingMethodsInfo = [];
        $collection = $this->tableRateCollection->create();
        $tableRates = $collection->getData();
        foreach ($tableRates as $rate) {
            // Determine the condition type (weight, price, or number of items)
            $conditionType = null;
            $conditionValue = null;
            if ($rate['condition_name'] === 'package_weight') {
                $conditionType = 'weight';
                $conditionValue = $rate['condition_value'];
            } elseif ($rate['condition_name'] === 'package_value_with_discount') {
                $conditionType = 'price';
                $conditionValue = $rate['condition_value'];
            } elseif ($rate['condition_name'] === 'package_qty') {
                $conditionType = 'item_qty';
                $conditionValue = $rate['condition_value'];
            }
            $shippingMethodsInfo[] = [
                'price' => $rate['price'],
                'country' => $rate['dest_country'],
                'state' => $rate['dest_region_id'],
                'zip' => $rate['dest_zip'],
                'condition' => [
                    'type' => $conditionType,
                    'amount' => $conditionValue,
                ],
            ];
        }
        return $shippingMethodsInfo;
    }
}
