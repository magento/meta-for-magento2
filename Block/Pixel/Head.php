<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

class Head extends Common {

  protected $_aamFieldsExtractorHelper;

  public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Framework\ObjectManagerInterface $objectManager,
    \Magento\Framework\Registry $registry,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
    array $data = [],
    \Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper $aamFieldsExtractorHelper
  ){
    parent::__construct($context, $objectManager, $registry, $fbeHelper, $magentoDataHelper, $data);
    $this->_aamFieldsExtractorHelper = $aamFieldsExtractorHelper;
  }

  /**
   * Returns the user data that will be added in the pixel init code
   * @return string
   */
  public function getPixelInitCode(){
    $userDataArray = $this->_aamFieldsExtractorHelper->getNormalizedUserData();

    if($userDataArray){
      return json_encode(array_filter($userDataArray), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
    }
    return '{}';
  }

  /**
   * Create JS code with the data processing options if required
   * To learn about this options in Facebook Pixel, read:
   * https://developers.facebook.com/docs/marketing-apis/data-processing-options
   * @return string
  */
  public function getDataProcessingOptionsJSCode(){
    return "";
  }

  /**
   * Create the data processing options passed in the Pixel image tag
   * Read about this options in:
   * https://developers.facebook.com/docs/marketing-apis/data-processing-options
   * @return string
  */
  public function getDataProcessingOptionsImgTag(){
    return "";
  }
}
