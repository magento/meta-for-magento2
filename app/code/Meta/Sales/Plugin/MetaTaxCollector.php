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

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class MetaTaxCollector
{
    /**
     * Add the Meta calculated item tax to the QuoteDetails item, so it can be used by the MetaTaxCollector
     *
     * @param  CommonTaxCollector               $commonTaxCollector
     * @param  QuoteDetailsItemInterface        $result
     * @param  QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param  AbstractItem                     $item
     * @return QuoteDetailsItemInterface
     */
    public function afterMapItem(
        CommonTaxCollector               $commonTaxCollector,
        QuoteDetailsItemInterface        $result,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        AbstractItem                     $item
    ): QuoteDetailsItemInterface {

        if (isset($item->getData()["meta_tax"]) && isset($item->getData()["meta_tax_rate"])) {
            $result->setData("meta_tax", $item->getData("meta_tax"));
            $result->setData("meta_tax_rate", $item->getData("meta_tax_rate"));
        }
        return $result;
    }

    /**
     * Add the Meta calculated shipment to the QuoteDetails item, so it can be used by the MetaTaxCollector
     *
     * @param  CommonTaxCollector          $commonTaxCollector
     * @param  QuoteDetailsItemInterface   $result
     * @param  ShippingAssignmentInterface $shippingAssignment
     * @return QuoteDetailsItemInterface
     */
    public function afterGetShippingDataObject(
        CommonTaxCollector          $commonTaxCollector,
        QuoteDetailsItemInterface   $result,
        ShippingAssignmentInterface $shippingAssignment
    ): QuoteDetailsItemInterface {
        $address = $shippingAssignment->getShipping()->getAddress();

        if (isset($address->getData()["meta_tax"]) && isset($address->getData()["meta_tax_rate"])) {
            $result->setData("meta_tax", $address->getData("meta_tax"));
            $result->setData("meta_tax_rate", $address->getData("meta_tax_rate"));
        }

        return $result;
    }
}
