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
namespace Meta\Catalog\Setup;

use Meta\Catalog\Model\Config\Source\Product\GoogleProductCategory;
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
     * Uninstall constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
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

        // delete "sync_to_facebook_catalog category" attribute
        if ($eavSetup->getAttributeId(Category::ENTITY, 'sync_to_facebook_catalog')) {
            $eavSetup->removeAttribute($categoryTypeId, 'sync_to_facebook_catalog');
        }
    }
}
