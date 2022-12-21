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

namespace Facebook\BusinessExtension\Model\Product\Feed\Builder;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Currency;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Tools
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Tools constructor
     *
     * @param PriceCurrencyInterface $priceCurrency
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(PriceCurrencyInterface $priceCurrency, ObjectManagerInterface $objectManager)
    {
        $this->priceCurrency = $priceCurrency;
        $this->objectManager = $objectManager;
    }

    /**
     * @param $string
     * @return false|string|string[]|null
     */
    public function lowercaseIfAllCaps($string)
    {
        // if contains lowercase, don't update string
        if (!preg_match('/[a-z]/', $string)) {
            if (mb_strtoupper($string, 'utf-8') === $string) {
                return mb_strtolower($string, 'utf-8');
            }
        }
        return $string;
    }

    /**
     * @param $value
     * @return string
     */
    public function htmlDecode($value)
    {
        return strip_tags(html_entity_decode($value));
    }

    /**
     * Return formatted price with currency code. Examples: "9.99 USD", "27.02 GBP"
     *
     * @param $price
     * @param null $storeId
     * @return string
     */
    public function formatPrice($price, $storeId = null)
    {
        $currencyModel = $this->priceCurrency->getCurrency($storeId);
        $amount = $this->priceCurrency->convert($price, $storeId, $currencyModel);
        try {
            return sprintf(
                '%s %s',
                $currencyModel->formatTxt($amount, ['display' => Currency::NO_SYMBOL]),
                $currencyModel->getCode()
            );
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getUnitPrice(Product $product)
    {
        $value = $product->getPerUnitPriceValue();
        $unit = $product->getPerUnitPriceUnit();

        if (!($value && $unit)) {
            return '';
        }

        $currencyModel = $this->priceCurrency->getCurrency($product->getStoreId());

        try {
            return sprintf(
                "{'value':'%s','currency':'%s','unit':'%s'}",
                $currencyModel->formatTxt($value, ['display' => Currency::NO_SYMBOL]),
                $currencyModel->getCode(),
                $unit
            );
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Facebook product feed validator will throw an error
     * if a local URL like https://localhost/product.html was provided
     * so replacing with a dummy URL to allow for local testing
     *
     * @param string $url
     * @return string
     */
    public function replaceLocalUrlWithDummyUrl($url)
    {
        return str_replace('localhost', 'magento.com', $url);
    }

    /**
     * @param int $useMultiSource
     * @return InventoryInterface
     */
    public function getInventoryObject($useMultiSource = 0)
    {
        return $useMultiSource
            ? $this->objectManager->get('Facebook\BusinessExtension\Model\Product\Feed\Builder\MultiSourceInventory')
            : $this->objectManager->get('Facebook\BusinessExtension\Model\Product\Feed\Builder\Inventory');
    }
}
