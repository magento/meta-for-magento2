<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

class AddToCart extends Common {

  public function getProductInfoUrl() {
    return sprintf('%sfbe/Pixel/ProductInfoForAddToCart', $this->_fbeHelper->getBaseUrl());
  }

}
