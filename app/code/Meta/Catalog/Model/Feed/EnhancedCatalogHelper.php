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
namespace Meta\Catalog\Model\Feed;

use Meta\Catalog\Model\Config\ProductAttributes;
use Meta\BusinessExtension\Helper\FBEHelper;
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
