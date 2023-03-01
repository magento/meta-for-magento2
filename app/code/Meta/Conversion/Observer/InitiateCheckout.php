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

namespace Meta\Conversion\Observer;

use Magento\Quote\Api\Data\CartInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

use Meta\Conversion\Helper\ServerEventFactory;

class InitiateCheckout implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var ServerSideHelper
     */
    private ServerSideHelper $serverSideHelper;

    /**
     * @var ServerEventFactory
     */
    private ServerEventFactory $serverEventFactory;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param ServerSideHelper $serverSideHelper
     * @param ServerEventFactory $serverEventFactory
     * @param PricingHelper $pricingHelper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper,
        ServerEventFactory $serverEventFactory,
        PricingHelper $pricingHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->serverSideHelper = $serverSideHelper;
        $this->serverEventFactory = $serverEventFactory;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * Execute action method for the Observer
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $quote = $observer->getData('quote');
            $customData = [
                'currency'     => $this->magentoDataHelper->getCurrency(),
                'value'        => $this->magentoDataHelper->getCartTotal($quote),
                'content_type' => 'product',
                'content_ids'  => $this->getCartContentIds($quote),
                'num_items'    => $this->magentoDataHelper->getCartNumItems($quote),
                'contents'     => $this->getCartContents($quote),
                'custom_properties' => [
                    'source'           => $this->fbeHelper->getSource(),
                    'pluginVersion'    => $this->fbeHelper->getPluginVersion()
                ]
            ];
            $event = $this->serverEventFactory->createEvent('InitiateCheckout', array_filter($customData), $eventId);
            $this->serverSideHelper->sendEvent($event);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }

    /**
     * Return information about the cart items
     *
     * @link https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/custom-data/#contents
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getCartContents(CartInterface $quote): array
    {
        if (!$quote) {
            return [];
        }

        $contents = [];
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $product = $item->getProduct();
            $contents[] = [
                'product_id' => $this->magentoDataHelper->getContentId($product),
                'quantity' => (int) $item->getQty(),
                'item_price' => $item->getPrice(),
            ];
        }
        return $contents;
    }

    /**
     * Return the ids of the items added to the cart
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getCartContentIds(CartInterface $quote): array
    {
        if (!$quote) {
            return [];
        }
        $contentIds = [];

        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $contentIds[] = $this->magentoDataHelper->getContentId($item->getProduct());
        }
        return $contentIds;
    }
}
