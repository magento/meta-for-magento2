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

namespace Meta\Catalog\Model\Product\Feed;

use Magento\Bundle\Api\Data\LinkInterface as BundleLinkInterface;
use Magento\Bundle\Api\Data\OptionInterface as BundleOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Downloadable\Api\Data\LinkInterface as DownloadableLinkInterface;
use Magento\Downloadable\Api\Data\SampleInterface;
use Magento\Framework\Api\AttributeInterface;

class Mapper
{
    /**
     * Maps a custom option value into the Meta feed format
     *
     * @param ProductCustomOptionValuesInterface $value
     * @return array
     */
    private function mapProductCustomOptionValue(ProductCustomOptionValuesInterface $value): array
    {
        return [
            "price" => $value->getPrice(),
            "price_type" => $value->getPriceType(),
            "title" => $value->getTitle(),
            "sort_order" => $value->getSortOrder(),
            "option_type_id" => $value->getOptionTypeId(),
            "sku" => $value->getSku(),
        ];
    }

    /**
     * Maps a custom option into the Meta feed format
     *
     * @param ProductCustomOptionInterface $option
     * @return array
     */
    public function mapProductCustomOption(ProductCustomOptionInterface $option): array
    {
        return [
            "title" => $option->getTitle(),
            "type" => $option->getType(),
            "sort_order" => $option->getSortOrder(),
            "sku" => $option->getSku(),
            "is_required" => $option->getIsRequire(),
            "option_id" => $option->getOptionId(),
            "file_extension" => $option->getFileExtension(),
            "image_size_y" => $option->getImageSizeY(),
            "image_size_x" => $option->getImageSizeY(),
            "price_type" => $option->getPriceType(),
            "price" => $option->getPrice(),
            "values" => $option->getValues() == null
                ? null
                : array_map(function ($value) {
                    return $this->mapProductCustomOptionValue($value);
                }, $option->getValues())
        ];
    }

    /**
     * Maps a downloadable product link into the Meta feed format
     *
     * @param DownloadableLinkInterface $link
     * @return array
     */
    public function mapDownloadableProductLink(DownloadableLinkInterface $link) : array
    {
        return [
            'id' => $link->getId(),
            'link_url' => $link->getLinkUrl(),
            'link_type' => $link->getLinkType(),
            'link_file' => $link->getLinkFile(),
            'price' => $link->getPrice(),
            'title' => $link->getTitle(),
            'sort_order' => $link->getSortOrder(),
            'is_shareable' => $link->getIsShareable(),
            'number_of_downloads' => $link->getNumberOfDownloads(),
            'sample_file' => $link->getSampleFile(),
            'sample_type' => $link->getSampleType(),
            'sample_url' => $link->getSampleUrl(),
        ];
    }

    /**
     * Maps a downloadable product sample into the Meta feed format
     *
     * @param SampleInterface $sample
     * @return array
     */
    public function mapDownloadableProductSample(SampleInterface $sample) : array
    {
        return [
            'id' => $sample->getId(),
            'title' => $sample->getTitle(),
            'sort_order' => $sample->getSortOrder(),
            'sample_file' => $sample->getSampleFile(),
            'sample_type' => $sample->getSampleType(),
            'sample_url' => $sample->getSampleUrl(),
        ];
    }

    /**
     * Maps a bundle link into the Meta feed format
     *
     * @param BundleLinkInterface $link
     * @return array
     */
    public function mapLink(BundleLinkInterface $link): array
    {
        return [
            'id' => $link->getId(),
            'sku' => $link->getSku(),
            'position' => $link->getPosition(),
            'price' => $link->getPrice(),
            'price_type' => $link->getPriceType(),
            'can_change_quantity' => $link->getCanChangeQuantity(),
            'is_default' => $link->getIsDefault(),
            'quantity' => $link->getQty(),
        ];
    }

    /**
     * Maps, and optionally filters, an array of product links into the Meta feed format
     *
     * @param ProductLinkInterface[] $product_links
     * @param string $link_type
     * @return array
     */
    public function mapProductLinks(array $product_links, string $link_type = null): array
    {
        if ($link_type != null) {
            $product_links = array_filter($product_links, function ($product_link) use ($link_type) {
                return $product_link->getLinkType() == $link_type;
            });
        }

        return array_map(function ($product_link) {
            return $this->mapProductLink($product_link);
        }, $product_links);
    }

    /**
     * Maps a product link into the Meta feed format
     *
     * @param ProductLinkInterface $product_link
     * @return array
     */
    private function mapProductLink(ProductLinkInterface $product_link): array
    {
        return [
            'link_type' => $product_link->getLinkType(),
            'product_sku' => $product_link->getLinkedProductSku(),
            'position' => $product_link->getPosition(),
            'default_quantity' => $product_link->getExtensionAttributes()->getQty(),
        ];
    }

    /**
     * Maps a bundle option into the Meta feed format
     *
     * @param BundleOptionInterface $option
     * @return array
     */
    public function mapBundleOption(BundleOptionInterface $option) : array
    {
        $productLinks = $option->getProductLinks();
        return [
            'id' => $option->getOptionId(),
            'title' => $option->getTitle(),
            'sku' => $option->getSku(),
            'position' => $option->getPosition(),
            'type' => $option->getType(),
            'required' => $option->getRequired(),
            'products' => $productLinks == null ? null : array_map(function ($link) {
                return $this->mapLink($link);
            }, $productLinks),
        ];
    }
}
