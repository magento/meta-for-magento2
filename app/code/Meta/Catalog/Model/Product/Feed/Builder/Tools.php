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

namespace Meta\Catalog\Model\Product\Feed\Builder;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Escaper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Helper\Data as CatalogHelper;

class Tools
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * Tools constructor
     *
     * @param PriceCurrencyInterface $priceCurrency
     * @param Escaper $escaper
     * @param SystemConfig $systemConfig
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        Escaper $escaper,
        SystemConfig $systemConfig,
        CatalogHelper $catalogHelper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->escaper = $escaper;
        $this->systemConfig = $systemConfig;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * Change casing of string
     *
     * @param string $string
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
     * Returns html decoded string
     *
     * @param string $value
     * @return string
     */
    public function htmlDecode($value)
    {
        return strip_tags($this->escaper->escapeHtml($value));
    }

    /**
     * Return formatted price with currency code. Examples: "9.99 USD", "27.02 GBP"
     *
     * @param float $price
     * @param int $storeId
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
     * Returns product unit price
     *
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
     * Get product price
     *
     * @param Product $product
     * @return string
     */
    public function getProductPrice(Product $product)
    {
        $price = $this->systemConfig->isPriceInclTax()
            ? $this->catalogHelper->getTaxPrice($product, $product->getPrice(), true)
            : $product->getPrice();
        return $this->formatPrice($price, $product->getStoreId());
    }

    /**
     * Get product sale price
     *
     * @param Product $product
     * @return string
     */
    public function getProductSalePrice(Product $product)
    {
        if ($product->getFinalPrice() > 0 && $product->getPrice() > $product->getFinalPrice()) {
            $price = $this->systemConfig->isPriceInclTax()
                ? $this->catalogHelper->getTaxPrice($product, $product->getFinalPrice(), true)
                : $product->getFinalPrice();
            return $this->formatPrice($price, $product->getStoreId());
        }
        return '';
    }

    /**
     * Get product sale price effective date
     *
     * @param Product $product
     * @return string
     * @throws Exception
     */
    public function getProductSalePriceEffectiveDate(Product $product)
    {
        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate = $product->getSpecialToDate();

        $salePriceStartDate = '';
        if ($specialFromDate) {
            $salePriceStartDate = (new \DateTime($specialFromDate))->format('c');
        }
        $salePriceEndDate = '';
        if ($specialToDate) {
            $salePriceEndDate = (new \DateTime($specialToDate))->format('c');
        }
        if ($product->getSpecialPrice() && $salePriceStartDate || $salePriceEndDate) {
            return sprintf("%s/%s", $salePriceStartDate, $salePriceEndDate);
        }
        return '';
    }
}
