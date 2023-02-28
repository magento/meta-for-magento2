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

namespace Meta\Conversion\Block\Pixel;

use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;

/**
 * @api
 */
class Purchase extends Common
{
    /**
     * Get contents IDs
     *
     * @return string
     */
    public function getContentIDs()
    {
        $contentIds = [];
        /** @var Order $order */
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        if ($order) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                $contentIds[] = $this->getContentId($item->getProduct());
            }
        }
        return $this->arrayToCommaSeparatedStringValues($contentIds);
    }

    /**
     * Get value
     *
     * @return float|string|null
     */
    public function getValue()
    {
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        /** @var Order $order */
        if ($order) {
            $subtotal = $order->getSubTotal();
            if ($subtotal) {
                $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
                return $priceHelper->currency($subtotal, false, false);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get contents
     *
     * @return string
     */
    public function getContents()
    {
        $contents = [];
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        /** @var Order $order */
        if ($order) {
            $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                /** @var Product $product */
                // @todo reuse results from self::getContentIDs()
                $product = $item->getProduct();
                $price = $priceHelper->currency($product->getFinalPrice(), false, false);
                $content = '{id:"' . $product->getId() . '",quantity:' . (int)$item->getQtyOrdered()
                    . ',item_price:' . $price . '}';
                $contents[] = $content;
            }
        }
        return implode(',', $contents);
    }

    /**
     * Get event to observe name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_purchase';
    }
}
