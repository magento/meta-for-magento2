<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Setup;

use Facebook\BusinessExtension\Model\Config\ProductAttributes;
use Facebook\BusinessExtension\Model\Config\Source\Product\GoogleProductCategory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ProductAttributes
     */
    private $productAttributes;

    /**
     * Uninstall constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ProductAttributes $productAttributes
     */
    public function __construct(EavSetupFactory $eavSetupFactory, ProductAttributes $productAttributes)
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->productAttributes = $productAttributes;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     * @throws LocalizedException
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->getConnection()->dropTable('facebook_business_extension_config');
        $setup->getConnection()->delete('core_config_data', "path LIKE 'facebook/%'");

        $eavSetup = $this->eavSetupFactory->create();
        $productTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $categoryTypeId = $eavSetup->getEntityTypeId(Category::ENTITY);

        // delete "google_product_category" product attribute if installed by this extension
        if ($eavSetup->getAttributeId(Product::ENTITY, 'google_product_category')) {
            /** @var Attribute $attribute */
            $attribute = $eavSetup->getAttribute($productTypeId, 'google_product_category');
            if (isset($attribute['source_model']) && $attribute['source_model'] === GoogleProductCategory::class) {
                $eavSetup->removeAttribute($productTypeId, 'google_product_category');
            }
        }

        // delete product attributes based on configuration
        $attributesConfig = $this->productAttributes->getAttributesConfig();
        foreach ($attributesConfig as $attrCode => $config) {
            if ($eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                $eavSetup->removeAttribute($productTypeId, $attrCode);
            }
        }

        // delete unit price attributes if exist
        if (method_exists($this->productAttributes, 'getUnitPriceAttributesConfig')) {
            $attributesConfig = $this->productAttributes->getUnitPriceAttributesConfig();
            foreach ($attributesConfig as $attrCode => $config) {
                if ($eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                    $eavSetup->removeAttribute($productTypeId, $attrCode);
                }
            }
        }

        // delete "Facebook Attribute Group" from all product attribute sets
        $setup->getConnection()->delete(
            'eav_attribute_group',
            ['attribute_group_name = ?' => $this->productAttributes->getAttributeGroupName()]
        );

        // delete "sync_to_facebook_catalog category" attribute
        if ($eavSetup->getAttributeId(Category::ENTITY, 'sync_to_facebook_catalog')) {
            $eavSetup->removeAttribute($categoryTypeId, 'sync_to_facebook_catalog');
        }
    }
}
