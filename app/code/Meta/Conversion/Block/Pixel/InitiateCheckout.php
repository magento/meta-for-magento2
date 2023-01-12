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
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

/**
 * @api
 */
class InitiateCheckout extends Common
{
    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param Registry $registry
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param SystemConfig $systemConfig
     * @param PricingHelper $pricingHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Registry $registry,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        SystemConfig $systemConfig,
        PricingHelper $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $objectManager, $registry, $fbeHelper, $magentoDataHelper, $systemConfig, $data);
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @return string
     */
    public function getContentIDs()
    {
        $contentIds = [];
        $items = $this->magentoDataHelper->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $contentIds[] = $this->getContentId($item->getProduct());
        }
        return $this->arrayToCommaSeparatedStringValues($contentIds);
    }

    public function getValue()
    {
        if (!$this->magentoDataHelper->getQuote()) {
            return null;
        }
        $subtotal = $this->magentoDataHelper->getQuote()->getSubtotal();
        if ($subtotal) {
            return $this->pricingHelper->currency($subtotal, false, false);
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getContents()
    {
        if (!$this->magentoDataHelper->getQuote()) {
            return '';
        }
        $contents = [];
        $items = $this->magentoDataHelper->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $product = $item->getProduct();
            $price = $this->pricingHelper->currency($product->getFinalPrice(), false, false);
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
        if (!$this->magentoDataHelper->getQuote()) {
            return null;
        }
        $numItems = 0;
        $items = $this->magentoDataHelper->getQuote()->getAllVisibleItems();
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
