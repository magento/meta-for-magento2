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

use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;
use Magento\Tax\Api\TaxCalculationInterface;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class MetaTaxCalculation
{
    /**
     * Update tax to the Meta calculated tax value for each line item and shipping
     *
     * @param TaxCalculationInterface $taxCalculation
     * @param TaxDetailsInterface $result
     * @param QuoteDetailsInterface $quoteDetails
     * @return TaxDetailsInterface
     */
    public function afterCalculateTax(
        TaxCalculationInterface $taxCalculation,
        TaxDetailsInterface     $result,
        QuoteDetailsInterface   $quoteDetails
    ): TaxDetailsInterface {
        $itemCodeToTaxMap = $this->buildItemCodeToTaxMap($quoteDetails);

        if ($itemCodeToTaxMap) {
            $this->updateItemsTax($result, $itemCodeToTaxMap);
        }

        return $result;
    }

    /**
     * Build a map between the item code and the tax Meta calculated for the item.
     *
     * If there is an item for which we do not have the Meta calculated tax, then return null
     *
     * @param QuoteDetailsInterface $quoteDetails
     * @return array|null
     */
    private function buildItemCodeToTaxMap(
        QuoteDetailsInterface $quoteDetails
    ): ?array {
        $items = $quoteDetails->getItems();
        // Empty arrays are falsey in PHP. This is both a null and contents check.
        if (!$items) {
            return null;
        }

        $itemCodeToTaxMap = [];
        foreach ($items as $item) {
            $data = $item->getData();
            if (!isset($data["meta_tax"]) || !isset($data["meta_tax_rate"])) {
                return null;
            }
            $itemCodeToTaxMap[$item->getCode()] = ["tax" => $data["meta_tax"], "tax_rate" => $data["meta_tax_rate"]];
        }

        return $itemCodeToTaxMap;
    }

    /**
     * Update the tax for the items
     *
     * @param TaxDetailsInterface $taxDetails
     * @param array $itemCodeToTaxMap
     * @return void
     */
    private function updateItemsTax(
        TaxDetailsInterface $taxDetails,
        array               $itemCodeToTaxMap
    ): void {
        if (!$taxDetails->getItems()) {
            return;
        }

        $metaTaxSetForItems = $this->checkMetaTaxAvailableForAllItems(
            $taxDetails->getItems(),
            $itemCodeToTaxMap
        );

        if ($metaTaxSetForItems) {
            foreach ($taxDetails->getItems() as $item) {
                $itemCode = $item->getCode();
                $metaTax = $itemCodeToTaxMap[$itemCode]["tax"];
                $metaTaxRate = $itemCodeToTaxMap[$itemCode]["tax_rate"];
                $this->updateTax($item, $metaTax, $metaTaxRate);
            }
        }
    }

    /**
     * Check if Meta calculated tax is available in the map for all the items, return false if it isn't
     *
     * @param TaxDetailsItemInterface[] $items
     * @param array $itemCodeToTaxMap
     * @return bool
     */
    private function checkMetaTaxAvailableForAllItems(
        array $items,
        array $itemCodeToTaxMap
    ): bool {
        foreach ($items as $item) {
            $itemCode = $item->getCode();
            if (!isset($itemCodeToTaxMap[$itemCode])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update the tax for an individual line item
     *
     * @param TaxDetailsItemInterface $item
     * @param float $tax
     * @param float $taxRate
     * @return void
     */
    private function updateTax(
        TaxDetailsItemInterface $item,
        float                   $tax,
        float                   $taxRate
    ): void {
        $item->setRowTax($tax);
        $item->setPriceInclTax($item->getPrice() * (1 + $taxRate));
        $item->setRowTotalInclTax($item->getRowTotal() + $tax);
        $item->setDiscountTaxCompensationAmount(0);
        $item->setAppliedTaxes([]);
        $item->setTaxPercent($taxRate);
    }
}
