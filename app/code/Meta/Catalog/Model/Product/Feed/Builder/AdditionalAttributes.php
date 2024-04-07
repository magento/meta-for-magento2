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

namespace Meta\Catalog\Model\Product\Feed\Builder;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Setup\CategorySetup;
use Meta\BusinessExtension\Helper\FBEHelper;

class AdditionalAttributes
{

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var Attribute
     */
    private $attributeFactory;

    /**
     * Constructor
     *
     * @param Attribute $attributeFactory
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        Attribute       $attributeFactory,
        FBEHelper       $fbeHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * Get All additional attributes of the product
     *
     * @param Product $product
     * @return array
     */
    public function getAdditionalMetadata(Product $product): array
    {
        $productAttributes = $this->getAdditionalMetadataAttributesList();
        $allAttributes = [];
        $exclusions = ['media_gallery'];
        foreach ($productAttributes as $attributeCode) {
            try {
                if (in_array($attributeCode, $exclusions)) {
                    continue;
                }
                $attributeData = $this->getCorrectText($product, $attributeCode);
                if ($attributeData) {
                    $allAttributes[$attributeCode] = $attributeData;
                }
            } catch (Exception $e) {
                $this->fbeHelper->log(
                    sprintf(
                        'Error with processing attribute %s for product %s.',
                        $attributeCode,
                        $product->getName()
                    )
                );
                $this->fbeHelper->logException($e);
                continue;
            }
        }
        return $allAttributes;
    }

    /**
     * Get value of custom attribute of a product
     *
     * @param Product $product
     * @param string $attributeCode
     * @return string
     */
    public function getCustomAttributeText(Product $product, string $attributeCode): string
    {
        try {
            $attributeData = $this->getCorrectText($product, $attributeCode);
            if ($attributeData) {
                if (is_array($attributeData)) {
                    return json_encode($attributeData);
                }
                if (is_scalar($attributeData)) {
                    return (string) $attributeData;
                }
                return '';
            }
        } catch (Exception $e) {
            $this->fbeHelper->log(
                sprintf(
                    'Error with processing attribute %s for product %s.',
                    $attributeCode,
                    $product->getName()
                )
            );
            $this->fbeHelper->logException($e);
        }
        return '';
    }

    /**
     * Get correct text for product attribute
     *
     * @param Product $product
     * @param string $attribute
     * @return mixed
     */
    public function getCorrectText(Product $product, string $attribute)
    {
        $attribute_data = $product->getData($attribute);
        if (!$attribute_data) {
            return false;
        }

        if (is_string($attribute_data)) {
            $text = $product->getAttributeText($attribute);
            return $text ?: $attribute_data;
        }
        return $attribute_data;
    }

    /**
     * Collect list of attribute codes for user-defined attributes
     *
     * @return array
     */
    public function getUserDefinedAttributesList(): array
    {
        return $this->getAttributeList(
            function ($attributeList) {
                $attributeList
                    ->addFieldToFilter('entity_type_id', ['eq' => CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID])
                    ->addFieldToFilter('is_user_defined', ['eq' => 1]);
            }
        );
    }

    /**
     * Collect list of attribute codes for additional metadata attributes
     *
     * @return array
     */
    public function getAdditionalMetadataAttributesList(): array
    {
        return $this->getAttributeList(
            function ($attributeList) {
                $attributeList
                    ->addFieldToFilter('entity_type_id', ['eq' => CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID])
                    ->addFieldToFilter('is_user_defined', ['eq' => 0]);
            }
        );
    }

    /**
     * Collect list of attribute codes, per given filter
     *
     * @param callable $attributeFilterFn
     * @return array
     */
    private function getAttributeList(callable $attributeFilterFn): array
    {
        $attributes = [];
        $attributeList = $this->attributeFactory->getCollection();
        if ($attributeList) {
            if ($attributeFilterFn) {
                $attributeFilterFn($attributeList);
            }
            $attributes = $attributeList->getColumnValues('attribute_code');
        }
        asort($attributes);
        return $attributes;
    }
}
