<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

class Head extends Common {
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
