<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\ObjectManagerInterface;

class Common extends \Magento\Framework\View\Element\Template {
  /**
   * @var \Magento\Framework\ObjectManagerInterface
   */
  protected $_objectManager;
  protected $_registry;
  protected $_fbeHelper;
  protected $_magentoDataHelper;

  public function __construct(
    Context $context,
    ObjectManagerInterface $objectManager,
    \Magento\Framework\Registry $registry,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
    array $data = []) {
    parent::__construct($context, $data);
    $this->_objectManager = $objectManager;
    $this->_registry = $registry;
    $this->_fbeHelper = $fbeHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
  }

  public function arrayToCommaSeperatedStringValues($a) {
    return implode(',', array_map(function ($i) { return '"'.$i.'"'; }, $a));
  }

  public function escapeQuotes($string) {
    return addslashes($string);
  }

  public function getFacebookPixelID() {
    return $this->_fbeHelper->getPixelID();
  }

  public function getSource() {
    return $this->_fbeHelper->getSource();
  }

  public function getMagentoVersion() {
    return $this->_fbeHelper->getMagentoVersion();
  }

  public function getPluginVersion() {
    return $this->_fbeHelper->getPluginVersion();
  }

  public function getFacebookAgentVersion() {
    return $this->_fbeHelper->getPartnerAgent();
  }

  public function getContentType() {
    return 'product';
  }

  public function getCurrency() {
    return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
  }

  public function logEvent($pixel_id, $pixel_event) {
    $this->_fbeHelper->logPixelEvent($pixel_id, $pixel_event);
  }

  public function getEventToObserveName(){
    return '';
  }

  public function trackServerEvent($eventId){
    $this->_eventManager->dispatch($this->getEventToObserveName(), ['eventId' => $eventId]);
  }
}
