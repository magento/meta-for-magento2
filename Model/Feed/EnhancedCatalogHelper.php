<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Feed;
use Facebook\BusinessExtension\Model\Config\ProductAttributes;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Catalog\Model\Product;

class EnhancedCatalogHelper
{
    private $fbe_helper;
    private $attribute_config;

    public function __construct(FBEHelper $fbe_helper, ProductAttributes $attribute_config)
    {
        $this->attribute_config = $attribute_config;
        $this->fbe_helper = $fbe_helper;
    }

    /**
     * @param Product $product
     * @param array $requests
     * @throws Zend_Locale_Exception
     * @return null
     */
    public function AssignECAttribute(Product $product, array &$requests)
    {
        $attr_config = $this->attribute_config->getAttributesConfig();
        foreach ($attr_config as $attrCode => $config)
        {
            $data = $product->getData($attrCode);
            if($data)
            {
                // facebook_capacity -> capacity
                $trimedAttrCode = substr($attrCode, 9);
                $requests[$trimedAttrCode] = $data;
            }
        }
        return null;
    }
}
