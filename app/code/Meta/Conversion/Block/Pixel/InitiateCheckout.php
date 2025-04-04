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

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * @api
 */
class InitiateCheckout extends Common
{
    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var MagentoDataHelper
     */
    private $magentoDataHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    private $customerSession;

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
     * @param CustomerSession $customerSession
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
        CustomerSession $customerSession,
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
        $this->magentoDataHelper = $magentoDataHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * Get content ids
     *
     * @return array
     */
    public function getContentIDs()
    {
        $contentIds = [];
        $items = $this->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $contentIds[] = $this->getContentId($item->getProduct());
        }
        return $contentIds;
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
     * @return array|string
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
            $contents[] = [
                'id' => $this->magentoDataHelper->getContentId($product),
                'quantity' => (int) $item->getQty(),
                'item_price' => $price,
            ];
        }
        return $contents;
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
     */
    public function getQuote(): Quote
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * Returns content's category
     *
     * @return string
     */
    public function getContentCategory(): string
    {
        $items = $this->getQuote()->getAllVisibleItems();
        $contentCategory = '';
        foreach ($items as $item) {
            $product = $item->getProduct();
            $contentCategory =  $this->magentoDataHelper->getCategoriesForProduct($product);
        }
        return $contentCategory;
    }

    /**
     * Get Content Type
     *
     * @return string
     */
    public function getContentTypeQuote(): string
    {
        return 'product';
    }

    public function getEventId(): ?string
    {
        $eventIds = $this->customerSession->getEventIds();
        if (is_array($eventIds) && array_key_exists($this->getEventToObserveName(), $eventIds)) {
            return $eventIds[$this->getEventToObserveName()];
        }

        return null;
    }
}
