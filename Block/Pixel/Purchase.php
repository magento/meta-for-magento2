<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;

class Purchase extends Common
{
    /**
     * @return string
     */
    public function getContentIDs()
    {
        $productIds = [];
        /** @var Order $order */
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        if ($order) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                $product = $item->getProduct();
                $productIds[] = $product->getId();
            }
        }
        return $this->arrayToCommaSeparatedStringValues($productIds);
    }

    public function getValue()
    {
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        /** @var Order $order */
        if ($order) {
            $subtotal = $order->getSubTotal();
            if ($subtotal) {
                $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
                return $priceHelper->currency($subtotal, false, false);
            }
        } else {
            return null;
        }
    }

    public function getContents()
    {
        $contents = [];
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        /** @var Order $order */
        if ($order) {
            $priceHelper = $this->objectManager->get(\Magento\Framework\Pricing\Helper\Data::class);
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
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_purchase';
    }
}
