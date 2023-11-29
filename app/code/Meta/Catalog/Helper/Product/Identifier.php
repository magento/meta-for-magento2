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

namespace Meta\Catalog\Helper\Product;

use Meta\Catalog\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Identifier
{
    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var string
     */
    private $identifierAttr;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @param SystemConfig $systemConfig
     * @param ProductRepository $productRepository
     */
    public function __construct(SystemConfig $systemConfig, ProductRepository $productRepository)
    {
        $this->identifierAttr = $systemConfig->getProductIdentifierAttr();
        $this->systemConfig = $systemConfig;
        $this->productRepository = $productRepository;
    }

    /**
     * Get product's identifier col name(SKU or entity ID, depending on configuration)
     *
     * @return string
     */
    public function getProductIdentifierColName(): string
    {
        if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return 'sku';
        } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return 'entity_id';
        }
        return '';
    }

    /**
     * Get product Retailer ID for Commerce (SKU or ID, depending on configuration)
     *
     * @param ProductInterface $product
     * @return int|string|bool
     */
    public function getMagentoProductRetailerId(ProductInterface $product)
    {
        if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $product->getSku();
        } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $product->getId();
        }
        return false;
    }

    /**
     * Get product ID other than retailerID for Commerce
     *
     * @param ProductInterface $product
     * @return int|string|bool
     */
    public function getProductIDOtherThanRetailerId(ProductInterface $product)
    {
        if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $product->getId();
        } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $product->getSku();
        }
        return false;
    }

    /**
     * Fetch product by retailer id and identifier attribute
     *
     * @param string|int $retailerId
     * @return ProductInterface|bool
     */
    public function getProductByFacebookRetailerId($retailerId)
    {
        try {
            return $this->productRepository->get($retailerId);
        } catch (NoSuchEntityException $e) {
            // Product not found
            return $this->productRepository->getById($retailerId);
        }
    }
}
