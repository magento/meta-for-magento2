<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

class AddToCart extends Common
{
    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductInfoUrl()
    {
        return sprintf('%sfbe/Pixel/ProductInfoForAddToCart', $this->fbeHelper->getBaseUrl());
    }
}
