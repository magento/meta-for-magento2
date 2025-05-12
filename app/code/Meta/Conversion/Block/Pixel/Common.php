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

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template\Context;
use Meta\Conversion\Helper\EventIdGenerator;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;

class Common extends \Magento\Framework\View\Element\Template
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    private $magentoDataHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Common constructor
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
        parent::__construct($context, $data);
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->systemConfig = $systemConfig;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Convert array to string
     *
     * @param array $a
     * @return string
     */
    public function arrayToCommaSeparatedStringValues($a)
    {
        return implode(',', array_map(function ($i) {
            return '"' . $i . '"';
        }, $a));
    }

    /**
     * Escape quotes
     *
     * @param string $string
     * @return string
     */
    public function escapeQuotes($string)
    {
        return $this->escaper->escapeQuote($string);
    }

    /**
     * Get pixel id
     *
     * @return mixed|null
     */
    public function getFacebookPixelID()
    {
        return $this->systemConfig->getPixelId();
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->fbeHelper->getSource();
    }

    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->fbeHelper->getMagentoVersion();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->fbeHelper->getPluginVersion();
    }

    /**
     * Get FB agent version
     *
     * @return string
     */
    public function getFacebookAgentVersion()
    {
        return $this->fbeHelper->getPartnerAgent();
    }

    /**
     * Get Content type
     *
     * @return string
     */
    public function getContentType()
    {
        return 'product';
    }

    /**
     * Get currency
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrency()
    {
        return strtolower($this->_storeManager->getStore()->getCurrentCurrency()->getCode());
    }

    /**
     * Log event data
     *
     * @param string $pixelId
     * @param string $pixelEvent
     */
    public function logEvent($pixelId, $pixelEvent)
    {
        $this->fbeHelper->logPixelEvent($pixelId, $pixelEvent);
    }

    /**
     * Track server event
     *
     * @param string $eventId
     */
    public function trackServerEvent($eventId)
    {
        $quote = $this->checkoutSession->getQuote();
        $lastOrder = $this->checkoutSession->getLastRealOrder();
        $this->_eventManager->dispatch(
            $this->getEventToObserveName(),
            ['eventId' => $eventId, 'quote' => $quote, 'lastOrder' => $lastOrder]
        );
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return '';
    }

    /**
     * Get content id
     *
     * @param Product $product
     * @return bool|int|string
     */
    public function getContentId(Product $product)
    {
        return $this->magentoDataHelper->getContentId($product);
    }

    /**
     * Get automatic matching flag
     *
     * @return bool|null
     */
    public function getAutomaticMatchingFlag(): ?bool
    {
        try {
            $storeId = $this->_storeManager->getStore()->getId();
            $settingsAsString = $this->systemConfig->getPixelAamSettings($storeId);
            if ($settingsAsString) {
                $settingsAsArray = json_decode($settingsAsString, true);
                if ($settingsAsArray && isset($settingsAsArray['enableAutomaticMatching'])) {
                    return (bool)$settingsAsArray['enableAutomaticMatching'];
                }
            }
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
        }
        return null;
    }

    /**
     * Get user data URL
     *
     * @return string
     */
    public function getUserDataUrl(): string
    {
        return $this->getUrl('fbe/pixel/userData');
    }

    /**
     * Get tracker url
     *
     * @return string
     */
    public function getTrackerUrl(): string
    {
        return $this->getUrl('fbe/pixel/tracker');
    }

    /**
     * Get event id
     *
     * @return string
     */
    public function getEventId(): string
    {
        return EventIdGenerator::guidv4();
    }
}
