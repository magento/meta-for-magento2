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
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Related;
use Magento\GroupedProduct\Ui\DataProvider\Product\Form\Modifier\Grouped;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Product\Feed\Mapper;

class UnsupportedProducts
{
    /**
     * @var Mapper
     */
    private Mapper $mapper;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * Constructor
     *
     * @param Mapper $mapper
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        Mapper       $mapper,
        FBEHelper    $fbeHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->mapper = $mapper;
    }

    /**
     * Retrieves the features of the product
     *
     * @param Product $product
     * @return array
     */
    public function getFeatures(Product $product) : array
    {
        $features = [];

        switch ($product->getTypeId()) {
            case 'bundle':
                $features[] = 'BUNDLE';
                break;
            case 'grouped':
                $features[] = 'GROUP';
                break;
            case 'downloadable':
            case 'digital':
            case 'virtual':
                $features[] = 'NON_SHIPPABLE';
                break;
        }

        $customizableOptions = $product->getOptions();
        if ($customizableOptions != null && count($customizableOptions) > 0) {
            $features[] = 'CUSTOMIZABLE';
        }

        return $features;
    }

    /**
     * Retrieves customizable options
     *
     * @param Product $product
     * @return ?array
     */
    public function getCustomizableOptions(Product $product) : ?array
    {
        try {
            $options = $product->getOptions();

            if ($options == null) {
                return null;
            }

            return array_map(function ($option) {
                return $this->mapper->mapProductCustomOption($option);
            }, $options);
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
            return null;
        }
    }

    /**
     * Retrieves digital options
     *
     * @param Product $product
     * @return ?array
     */
    public function getDigitalOptions(Product $product) : ?array
    {
        try {
            $downloadable_product_links = $product->getExtensionAttributes()->getDownloadableProductLinks();
            $downloadable_product_samples = $product->getExtensionAttributes()->getDownloadableProductSamples();

            if ($downloadable_product_links == null && $downloadable_product_samples == null) {
                return null;
            }

            return [
                'links' => array_map(function ($link) {
                    return $this->mapper->mapDownloadableProductLink($link);
                }, $downloadable_product_links),
                'samples' => array_map(function ($sample) {
                    return $this->mapper->mapDownloadableProductSample($sample);
                }, $downloadable_product_samples),
            ];
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
            return null;
        }
    }

    /**
     * Retrieves bundle options
     *
     * @param Product $product
     * @return ?array
     */
    public function getBundleOptions(Product $product): ?array
    {
        try {
            $bundle_product_options = $product->getExtensionAttributes()->getBundleProductOptions();
            $product_links = $product->getProductLinks();

            if ($bundle_product_options == null && $product_links == null) {
                return null;
            }

            return [
                'bundle' => $bundle_product_options == null
                    ? null
                    : array_map(function ($option) {
                        return $this->mapper->mapBundleOption($option);
                    }, $bundle_product_options),
                // Groups
                'associated' => $product_links == null
                    ? null
                    : (array)$this->mapper->mapProductLinks($product_links, Grouped::LINK_TYPE),
                'upsell' => $product_links == null
                    ? null
                    : (array)$this->mapper->mapProductLinks($product_links, Related::DATA_SCOPE_UPSELL),
            ];
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
            return null;
        }
    }
}
