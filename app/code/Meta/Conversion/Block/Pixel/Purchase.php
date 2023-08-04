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

namespace Meta\Conversion\Block\Pixel;

use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * @api
 */
class Purchase extends Common
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * Purchase constructor
     *
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param SystemConfig $systemConfig
     * @param Escaper $escaper
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        SystemConfig $systemConfig,
        Escaper $escaper,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $fbeHelper,
            $magentoDataHelper,
            $systemConfig,
            $escaper,
            $checkoutSession,
            $data
        );
        $this->fbeHelper = $fbeHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get contents IDs
     *
     * @return array
     */
    public function getContentIDs()
    {
        $contentIds = [];
        /** @var Order $order */
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        if ($order) {
            $items = $order->getAllVisibleItems();
            foreach ($items as $item) {
                $contentIds[] = $item->getSku();
            }
        }
        return $contentIds;
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
            $subtotal = $order->getGrandTotal();
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
     * @return array
     */
    public function getContents()
    {
        $contents = [];
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        /** @var Order $order */
        if ($order) {
            $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
            $items = $order->getAllVisibleItems();
            foreach ($items as $item) {
                /** @var Product $product */
                $product = $item->getProduct();
                $price = $priceHelper->currency($product->getFinalPrice(), false, false);
                $contents[] = [
                    'id' => $item->getSku(),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'item_price' => $price
                ];
            }
        }
        return $contents;
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

    /**
     * Get Number of Items
     *
     * @return int
     */
    public function getNumItems()
    {
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        return (int) $order->getTotalQtyOrdered();
    }

    /**
     * Get product name
     *
     * @return array
     */
    public function getContentName()
    {
        $productName = [];
        $order = $this->fbeHelper->getObject(\Magento\Checkout\Model\Session::class)->getLastRealOrder();
        if ($order) {
            $items = $order->getAllVisibleItems();
            foreach ($items as $item) {
                /** @var Product $product */
                $productName[] = $item->getName();
            }
        }
        return $productName;
    }

    /**
     * Get last real Order Entity id
     *
     * @return float|string|null
     */
    public function getLastOrderRealOrderEntityId()
    {
        return $this->checkoutSession->getLastRealOrder()->getEntityId();
    }
}
