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

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var Quote
     */
    private $quote;

    /**
     * Constructor
     *
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param SystemConfig $systemConfig
     * @param Escaper $escaper
     * @param CheckoutSession $checkoutSession
     * @param PricingHelper $pricingHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        SystemConfig $systemConfig,
        Escaper $escaper,
        CheckoutSession $checkoutSession,
        PricingHelper $pricingHelper,
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
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * Get content ids
     *
     * @return string
     */
    public function getContentIDs()
    {
        $contentIds = [];
        $items = $this->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $contentIds[] = $this->getContentId($item->getProduct());
        }
        return $this->arrayToCommaSeparatedStringValues($contentIds);
    }

    /**
     * Get value
     *
     * @return float|null
     */
    public function getValue()
    {
        return $this->magentoDataHelper->getCartTotal($this->getQuote());
    }

    /**
     * Get all contents
     *
     * @return string
     */
    public function getContents()
    {
        if (!$this->getQuote()) {
            return '';
        }
        $contents = [];
        $items = $this->getQuote()->getAllVisibleItems();
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
     * Get number of items
     *
     * @return int|null
     */
    public function getNumItems()
    {
        return $this->magentoDataHelper->getCartNumItems($this->getQuote());
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_initiate_checkout';
    }

    /**
     * Get active quote
     *
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): Quote
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }
}
