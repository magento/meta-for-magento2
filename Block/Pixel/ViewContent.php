<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ViewContent extends Common
{
    /**
     * @return string
     */
    public function getContentIDs()
    {
        $product_ids = [];
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            $product_ids[] = $product->getId();
        }
        return $this->arrayToCommaSeparatedStringValues($product_ids);
    }

    /**
     * @return string|null
     */
    public function getContentName()
    {
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            return $this->escapeQuotes($product->getName());
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        $product = $this->registry->registry('current_product');
        return ($product->getTypeId() == Configurable::TYPE_CODE) ? 'product_group' : 'product';
    }

    /**
     * @return string|null
     */
    public function getContentCategory()
    {
        $product = $this->registry->registry('current_product');
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

    public function getValue()
    {
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            $price = $product->getFinalPrice();
            $priceHelper = $this->fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
            return $priceHelper->currency($price, false, false);
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_view_content';
    }
}
