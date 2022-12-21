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

namespace Facebook\BusinessExtension\Block\Pixel;

class InitiateCheckout extends Common
{
    /**
     * @return string
     */
    public function getContentIDs()
    {
        $contentIds = [];
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->fbeHelper->getObject(\Magento\Checkout\Model\Cart::class);
        $items = $cart->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $contentIds[] = $this->getContentId($item->getProduct());
        }
        return $this->arrayToCommaSeparatedStringValues($contentIds);
    }

    public function getValue()
    {
        $cart = $this->fbeHelper->getObject(\Magento\Checkout\Model\Cart::class);
        if (!$cart || !$cart->getQuote()) {
            return null;
        }
        $subtotal = $cart->getQuote()->getSubtotal();
        if ($subtotal) {
            $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
            return $priceHelper->currency($subtotal, false, false);
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getContents()
    {
        $cart = $this->fbeHelper->getObject(\Magento\Checkout\Model\Cart::class);
        if (!$cart || !$cart->getQuote()) {
            return '';
        }
        $contents = [];
        $items = $cart->getQuote()->getAllVisibleItems();
        $priceHelper = $this->objectManager->get(\Magento\Framework\Pricing\Helper\Data::class);
        foreach ($items as $item) {
            $product = $item->getProduct();
            $price = $priceHelper->currency($product->getFinalPrice(), false, false);
            $content = '{id:"' . $product->getId() . '",quantity:' . (int)$item->getQty()
                    . ',item_price:' . $price . "}";
            $contents[] = $content;
        }
        return implode(',', $contents);
    }

    /**
     * @return int|null
     */
    public function getNumItems()
    {
        $cart = $this->fbeHelper->getObject(\Magento\Checkout\Model\Cart::class);
        if (!$cart || !$cart->getQuote()) {
            return null;
        }
        $numItems = 0;
        $items = $cart->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $numItems += $item->getQty();
        }
        return $numItems;
    }

    /**
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_initiate_checkout';
    }
}
