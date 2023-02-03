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

namespace Meta\Conversion\Block\Pixel;

/**
 * @api
 */
class ViewContent extends Common
{
    /**
     * Return content ids
     *
     * @return string
     */
    public function getContentIDs()
    {
        $contentIds = [];
        $product = $this->getCurrentProduct();
        if ($product && $product->getId()) {
            $contentIds[] = $this->getContentId($product);
        }
        return $this->arrayToCommaSeparatedStringValues($contentIds);
    }

    /**
     * Returns content name
     *
     * @return string|null
     */
    public function getContentName()
    {
        $product = $this->getCurrentProduct();
        if ($product && $product->getId()) {
            return $this->escapeQuotes($product->getName());
        } else {
            return null;
        }
    }

    /**
     * Returns content type
     *
     * @return string
     */
    public function getContentType()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->getCurrentProduct();
        return $this->magentoDataHelper->getContentType($product);
    }

    /**
     * Returns content's category
     *
     * @return string|null
     */
    public function getContentCategory()
    {
        $product = $this->getCurrentProduct();
        $categoryIds = $product->getCategoryIds();
        if (count($categoryIds) > 0) {
            $categoryNames = [];
            $categoryModel = $this->fbeHelper->getObject(\Magento\Catalog\Model\Category::class);
            foreach ($categoryIds as $category_id) {
                // @todo do not load category model in loop - this can be a performance killer, use category collection
                $category = $categoryModel->load($category_id);
                $categoryNames[] = $category->getName();
            }
            return $this->escapeQuotes(implode(',', $categoryNames));
        } else {
            return null;
        }
    }

    /**
     * Return currency
     *
     * @return string
     */
    public function getValue()
    {
        $product = $this->getCurrentProduct();
        if ($product && $product->getId()) {
            $price = $product->getFinalPrice();
            $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
            return $priceHelper->currency($price, false, false);
        } else {
            return null;
        }
    }

    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_view_content';
    }

    /**
     * Returns Product id
     *
     * @return mixed
     */
    public function getProductId()
    {
        return $this->getCurrentProduct()->getId();
    }

    /**
     * Returns current product
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentProduct()
    {
        return $this->getLayout()->getBlock('product.info')->getProduct();
    }
}
