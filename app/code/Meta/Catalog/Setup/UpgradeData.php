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

use Exception;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Logger\Logger;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var FBEHelper
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param FBEHelper $helper
     * @param Logger $logger
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        EavSetupFactory      $eavSetupFactory,
        FBEHelper            $helper,
        Logger               $logger,
        SystemConfig         $systemConfig
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->systemConfig = $systemConfig;
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException|\Zend_Validate_Exception
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface   $context
    )
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $this->helper->log("getVersion" . $context->getVersion());

        // introducing google product category in 1.2.2
        if (version_compare($context->getVersion(), '1.2.2') < 0) {
            $attrCode = 'google_product_category';
            if (!$eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                try {
                    $eavSetup->addAttribute(Product::ENTITY, $attrCode, [
                        'group' => 'General',
                        'type' => 'varchar',
                        'label' => 'Google Product Category',
                        'input' => 'select',
                        'source' => 'Meta\Catalog\Model\Config\Source\Product\GoogleProductCategory',
                        'required' => false,
                        'sort_order' => 10,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'is_used_in_grid' => true,
                        'is_visible_in_grid' => true,
                        'is_filterable_in_grid' => true,
                        'visible' => true,
                        'is_html_allowed_on_front' => false,
                        'visible_on_front' => false
                    ]);
                } catch (Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }

        // user can config if they want to sync a category or not
        if (version_compare($context->getVersion(), '1.4.2') < 0) {
            $attrCode = "sync_to_facebook_catalog";
            $eavSetup->removeAttribute(Product::ENTITY, $attrCode);
            if (!$eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Category::ENTITY,
                    $attrCode,
                    [
                        'type' => 'int',
                        'input' => 'boolean',
                        'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                        'visible' => true,
                        'default' => "1",
                        'required' => false,
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'Display Settings',
                    ]
                );
            }
        }

        // remove FB attributes from products admin grid
        if (version_compare($context->getVersion(), '1.4.3') < 0) {

            $removeAttributeFromGrid = function ($attrCode) use ($eavSetup) {
                $attrId = $eavSetup->getAttributeId(Product::ENTITY, $attrCode);
                if ($attrId) {
                    $eavSetup->updateAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        $attrId,
                        [
                            'is_used_in_grid' => false,
                            'is_visible_in_grid' => false,
                            'is_filterable_in_grid' => false,
                        ]
                    );
                }
            };

            $removeAttributeFromGrid('google_product_category');
        }

        if (version_compare($context->getVersion(), '2.0.0') < 0) {
            // create send_to_facebook product attributes
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
            $attributeSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);
            $attributesToCreate = [
                'send_to_facebook' => [
                    'group' => 'General',
                    'type' => 'int',
                    'label' => 'Send to Facebook',
                    'default' => 1,
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'input' => 'select',
                    'visible' => true,
                    'sort_order' => 45
                ]
            ];
            $commonConfig = [
                'required' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => false,
                'global' => ScopedAttributeInterface::SCOPE_STORE
            ];
            foreach ($attributesToCreate as $attrCode => $attrConfig) {
                if (!$eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                    try {
                        $eavSetup->addAttribute(Product::ENTITY, $attrCode, array_merge($attrConfig, $commonConfig));
                        $eavSetup->addAttributeToGroup(
                            $entityTypeId,
                            $attributeSetId,
                            $attrConfig['group'],
                            $attrCode,
                            10
                        );
                    } catch (Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }

        $setup->endSetup();
    }
}
