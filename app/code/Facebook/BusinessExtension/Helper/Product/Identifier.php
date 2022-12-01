<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper\Product;

use Facebook\BusinessExtension\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Identifier
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var string
     */
    private $identifierAttr;

    /**
     * @param SystemConfig $systemConfig
     * @param ProductRepository $productRepository
     */
    public function __construct(SystemConfig $systemConfig, ProductRepository $productRepository)
    {
        $this->identifierAttr = $systemConfig->getProductIdentifierAttr();
        $this->productRepository = $productRepository;
    }

    /**
     * Get product's identifier (SKU or ID, depending on configuration)
     *
     * @param ProductInterface $product
     * @return bool|int|string
     */
    protected function getProductIdentifier(ProductInterface $product)
    {
        if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $product->getSku();
        } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $product->getId();
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
     * Get product Content ID for Pixel and CAPI
     *
     * @param ProductInterface $product
     * @return bool|int|string
     */
    public function getContentId(ProductInterface $product)
    {
        return $this->getMagentoProductRetailerId($product);
    }

    /**
     * @param $retailerId
     * @return ProductInterface|bool
     * @throws LocalizedException
     */
    public function getProductByFacebookRetailerId($retailerId)
    {
        try {
            if ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
                return $this->productRepository->get($retailerId);
            } elseif ($this->identifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
                return $this->productRepository->getById($retailerId);
            }
            throw new LocalizedException(__('Invalid FB product identifier configuration'));
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__(sprintf(
                'Product with %s %s does not exist in Magento catalog',
                strtoupper($this->identifierAttr),
                $retailerId
            )));
        }
    }
}
