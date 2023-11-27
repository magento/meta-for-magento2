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
     * Get product's identifier (SKU or ID, depending on configuration)
     *
     * @param ProductInterface $product
     * @return bool|int|string
     */
    private function getProductIdentifier(ProductInterface $product)
    {
        if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $product->getSku();
        } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $product->getId();
        }
        return false;
    }

    /**
     * Get product's other identifier for passing to Meta for product identifications
     *
     * @param ProductInterface $product
     * @return bool|int|string
     */
    private function getOtherProductIdentifier(ProductInterface $product)
    {
        // If identifier is set to sku then we pass magento id, otherwise in case of magento id, we pass sku
        // This is done to pass both different type of IDs to meta for product deduplication
        if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $product->getId();
        } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $product->getSku();
        }
        return false;
    }

    /**
     * Get product Retailer ID for Commerce
     *
     * @param ProductInterface $product
     * @return int|string|bool
     */
    public function getMagentoProductRetailerId(ProductInterface $product)
    {
        return $this->getProductIdentifier($product);
    }

    /**
     * Get product ID other than retailerID for Commerce
     *
     * @param ProductInterface $product
     * @return int|string|bool
     */
    public function getProductIDOtherThanRetailerId(ProductInterface $product)
    {
        return $this->getOtherProductIdentifier($product);
    }

    /**
     * Get product by Facebook retailer id
     *
     * @param string|int $retailerId
     * @return ProductInterface|bool
     * @throws LocalizedException
     */
    public function getProductByFacebookRetailerId($retailerId)
    {
        // Sometimes (without realizing), seller catalogs will be set up so that the Retailer ID they pass to Meta is
        // the entity ID of their magento product, not the SKU. There are legitimate reasons for sellers to do this,
        // but if they don't update their extension settings, calls to fetch magento products will fail.
        // This logic catches the product load failure, and makes an attempt to load by the inverted identifier instead
        // (Sku -> Entity Id, Entity Id -> Sku). If the config was misset, it will gracefully flip to the correct value.
        $product = $this->fetchProduct($retailerId, $this->identifierAttr);

        if (!$product) {
            // Switch identifier attribute
            $newIdentifierAttr = $this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU
                ? IdentifierConfig::PRODUCT_IDENTIFIER_ID
                : IdentifierConfig::PRODUCT_IDENTIFIER_SKU;

            $product = $this->fetchProduct($retailerId, $newIdentifierAttr);

            if ($product) {
                $this->systemConfig->setProductIdentifierAttr($newIdentifierAttr);
                $this->identifierAttr = $newIdentifierAttr;
            } else {
                throw new LocalizedException(__(sprintf(
                    'Product with %s %s does not exist in Magento catalog',
                    strtoupper($this->identifierAttr),
                    $retailerId
                )));
            }
        }

        return $product;
    }

    /**
     * Fetch product by retailer id and identifier attribute
     *
     * @param string|int $retailerId
     * @param string $identifierAttr
     * @return ProductInterface|bool
     */
    private function fetchProduct($retailerId, string $identifierAttr)
    {
        try {
            if ($identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
                return $this->productRepository->get($retailerId);
            } elseif ($identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
                return $this->productRepository->getById($retailerId);
            }
        } catch (NoSuchEntityException $e) {
            // Product not found
            return false;
        }
        return false;
    }
}
