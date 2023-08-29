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

use Magento\OfflineShipping\Model\Carrier\Flatrate;
use Magento\OfflineShipping\Model\Carrier\Freeshipping;
use Magento\OfflineShipping\Model\Carrier\Tablerate;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CollectionFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;

class ShippingData
{
    /**
     * @var Flatrate
     */
    protected Flatrate $flatRateModel;

    /**
     * @var Tablerate
     */
    protected Tablerate $tableRateModel;

    /**
     * @var Freeshipping
     */
    protected Freeshipping $freeShippingModel;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $tableRateCollection;

    public const ATTR_ENABLED = 'enabled';
    public const ATTR_TITLE = 'title';
    public const ATTR_METHOD_NAME = 'method_name';
    public const ATTR_SHIPPING_METHODS = 'shipping_methods';
    public const ATTR_HANDLING_FEE = 'handling_fee';
    public const ATTR_HANDLING_FEE_TYPE = 'handling_fee_type';
    public const ATTR_SHIPPING_FEE_TYPE = 'shipping_fee_type';
    public const ATTR_FREE_SHIPPING_MIN_ORDER_AMOUNT = 'free_shipping_minimum_order_amount';

    /**
     * @param Flatrate $flatRateModel
     * @param Tablerate $tableRateModel
     * @param Freeshipping $freeShippingModel
     * @param CollectionFactory $tableRateCollection
     */
    public function __construct(
        Flatrate          $flatRateModel,
        Tablerate         $tableRateModel,
        Freeshipping      $freeShippingModel,
        CollectionFactory $tableRateCollection
    ) {
        $this->flatRateModel = $flatRateModel;
        $this->tableRateModel = $tableRateModel;
        $this->freeShippingModel = $freeShippingModel;
        $this->tableRateCollection = $tableRateCollection;
    }

    /**
     * A function that builds a table rates shipping profile
     *
     * @return array
     */
    public function buildTableRatesProfile(): array
    {
        return $this->buildShippingProfile($this->tableRateModel, true);
    }

    /**
     * A function that builds a flat-rate shipping profile
     *
     * @return array
     */
    public function buildFlatRateProfile(): array
    {
        return $this->buildShippingProfile($this->flatRateModel, false);
    }

    /**
     * A function that builds a free shipping profile
     *
     * @return array
     */
    public function buildFreeShippingProfile(): array
    {
        return $this->buildShippingProfile($this->freeShippingModel, false);
    }

    /**
     * Returns shipping profiles based on the AbstractModel carrier passed in
     *
     * @param AbstractCarrier $model
     * @param bool $tableRates
     * @return array
     */
    private function buildShippingProfile(AbstractCarrier $model, bool $tableRates): array
    {
        $isEnabled = $model->getConfigData('active');
        $title = $model->getConfigData('title');
        $methodName = $model->getConfigData('name');
        $price = (float)$model->getConfigData("price") ?? 0.0;
        $allowedCountries = $model->getConfigData('specificcountry');
        if ($tableRates) {
            $shippingMethods = $this->getShippingMethodsInfoForTableRates();
        } else {
            $shippingMethods = $this->buildShippingMethodsInfo($allowedCountries, $price);
        }

        $freeShippingThreshold = $model->getConfigData('free_shipping_subtotal');

        $handlingFee = $model->getConfigData('handling_fee');
        $handlingFeeType = $model->getConfigData('handling_type');
        $shippingType = $model->getConfigData('type');
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
