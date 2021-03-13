<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

class Common extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    protected $magentoDataHelper;

    /**
     * Common constructor
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param Registry $registry
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Registry $registry,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->objectManager = $objectManager;
        $this->registry = $registry;
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
    }

    /**
     * @param $a
     * @return string
     */
    public function arrayToCommaSeparatedStringValues($a)
    {
        return implode(',', array_map(function ($i) {
            return '"' . $i . '"';
        }, $a));
    }

    /**
     * @param $string
     * @return string
     */
    public function escapeQuotes($string)
    {
        return addslashes($string);
    }

    /**
     * @return mixed|null
     */
    public function getFacebookPixelID()
    {
        return $this->fbeHelper->getPixelID();
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->fbeHelper->getSource();
    }

    /**
     * @return mixed
     */
    public function getMagentoVersion()
    {
        return $this->fbeHelper->getMagentoVersion();
    }

    /**
     * @return mixed
     */
    public function getPluginVersion()
    {
        return $this->fbeHelper->getPluginVersion();
    }

    /**
     * @return string
     */
    public function getFacebookAgentVersion()
    {
        return $this->fbeHelper->getPartnerAgent();
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return 'product';
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrency()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @param $pixelId
     * @param $pixelEvent
     */
    public function logEvent($pixelId, $pixelEvent)
    {
        $this->fbeHelper->logPixelEvent($pixelId, $pixelEvent);
    }

    /**
     * @param $eventId
     */
    public function trackServerEvent($eventId)
    {
        $this->_eventManager->dispatch($this->getEventToObserveName(), ['eventId' => $eventId]);
    }

    /**
     * @return string
     */
    public function getEventToObserveName()
    {
        return '';
    }
}
