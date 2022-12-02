<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Feed;

use Facebook\BusinessExtension\Model\Config\ProductAttributes;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Catalog\Model\Product;

class EnhancedCatalogHelper
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var ProductAttributes
     */
    private $attributeConfig;

    /**
     * EnhancedCatalogHelper constructor
     *
     * @param FBEHelper $fbeHelper
     * @param ProductAttributes $attributeConfig
     */
    public function __construct(FBEHelper $fbeHelper, ProductAttributes $attributeConfig)
    {
        $this->attributeConfig = $attributeConfig;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * @param Product $product
     * @param array $requests
     * @return null
     */
    public function assignECAttribute(Product $product, array &$requests)
    {
        $attrConfig = $this->attributeConfig->getAttributesConfig();
        foreach ($attrConfig as $attrCode => $config) {
            $data = $product->getData($attrCode);
            if ($data) {
                // facebook_capacity -> capacity
                $trimmedAttrCode = substr($attrCode, 9);
                $requests[$trimmedAttrCode] = $data;
            }
        }
        return null;
    }
}
