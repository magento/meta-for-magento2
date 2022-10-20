<?php

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Setup;

use Exception;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Logger\Logger;
use Facebook\BusinessExtension\Model\Config\ProductAttributes;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
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
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * Attribute set factory
     *
     * @var SetFactory
     */
    private $attributeSetFactory;

    /**
     * contains fb attribute config
     *
     * @var ProductAttributes
     */
    private $attributeConfig;

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
     * @param CategorySetupFactory $categorySetupFactory
     * @param SetFactory $attributeSetFactory
     * @param ProductAttributes $attributeConfig
     * @param FBEHelper $helper
     * @param Logger $logger
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        EavSetupFactory      $eavSetupFactory,
        CategorySetupFactory $categorySetupFactory,
        SetFactory           $attributeSetFactory,
        ProductAttributes    $attributeConfig,
        FBEHelper            $helper,
        Logger               $logger,
        SystemConfig         $systemConfig
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeConfig = $attributeConfig;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Retrieve the min Attribute Group Sort order, and plus one, we want to put fb attribute group the second place.
     * method stolen from Magento\Eav\Setup\EavSetup::getAttributeGroupSortOrder()
     *
     * @param EavSetup $eavSetup
     * @param int|string $entityTypeId
     * @param int|string $setId
     * @return int
     * @throws LocalizedException
     */
    private function getMinAttributeGroupSortOrder(EavSetup $eavSetup, $entityTypeId, $setId)
    {
        $bind = ['attribute_set_id' => $eavSetup->getAttributeSetId($entityTypeId, $setId)];
        $select = $eavSetup->getSetup()->getConnection()->select()->from(
            $eavSetup->getSetup()->getTable('eav_attribute_group'),
            'MIN(sort_order)'
        )->where(
            'attribute_set_id = :attribute_set_id'
        );
        $sortOrder = $eavSetup->getSetup()->getConnection()->fetchOne($select, $bind) + 1;
        return $sortOrder;
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
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

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
                        'source' => 'Facebook\BusinessExtension\Model\Config\Source\Product\GoogleProductCategory',
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

        if (version_compare($context->getVersion(), '1.2.0') < 0) {
            $attributeConfig = $this->attributeConfig->getAttributesConfig();
            foreach ($attributeConfig as $attrCode => $config) {
                // verify if already installed before
                if (!$eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                    //Create the attribute
                    $this->helper->log($attrCode . " not exist before, process it");
                    //  attribute does not exist
                    // add a new attribute
                    // and assign it to the "FacebookAttributeSet" attribute set
                    $eavSetup->addAttribute(
                        Product::ENTITY,
                        $attrCode,
                        [
                            'type' => $config['type'],
                            'label' => $config['label'],
                            'input' => $config['input'],
                            'source' => $config['source'],
                            'note' => $config['note'],
                            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                            'required' => false,
                            'user_defined' => true,
                            'is_used_in_grid' => true,
                            'is_visible_in_grid' => true,
                            'is_filterable_in_grid' => true,
                            'visible' => true,
                            'is_html_allowed_on_front' => false,
                            'searchable' => false,
                            'filterable' => false,
                            'comparable' => false,
                            'visible_on_front' => false,
                            'used_in_product_listing' => true,
                            'unique' => false,
                            'attribute_set' => 'FacebookAttributeSet'
                        ]
                    );
                } else {
                    $this->helper->log($attrCode . " already installed, skip");
                }
            }

            /**
             * Create a custom attribute group in all attribute sets
             * And, Add attribute to that attribute group for all attribute sets
             */
            $attributeGroupName = $this->attributeConfig->getAttributeGroupName();

            // get the catalog_product entity type id/code
            $entityTypeId = $categorySetup->getEntityTypeId(Product::ENTITY);

            // get the attribute set ids of all the attribute sets present in your Magento store
            $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);

            foreach ($attributeSetIds as $attributeSetId) {
                $attr_group_sort_order = $this->getMinAttributeGroupSortOrder(
                    $eavSetup,
                    $entityTypeId,
                    $attributeSetId
                );
                $eavSetup->addAttributeGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $attributeGroupName,
                    $attr_group_sort_order // sort order
                );

                foreach ($attributeConfig as $attributeCode => $config) {
                    // get the newly create attribute group id
                    $attributeGroupId = $eavSetup->getAttributeGroupId(
                        $entityTypeId,
                        $attributeSetId,
                        $attributeGroupName
                    );

                    // add attribute to group
                    $categorySetup->addAttributeToGroup(
                        $entityTypeId,
                        $attributeSetId,
                        $attributeGroupName, // attribute group
                        $attributeCode,
                        $config['sort_order']
                    );
                }
            }
        }
        // change attribute code facebook_software_system_requirements -> facebook_system_requirements
        // due to 30 length limit
        if (version_compare($context->getVersion(), '1.2.5') < 0) {
            $oldAttrCode = 'facebook_software_system_requirements';
            $newAttrCode = 'facebook_system_requirements';

            $oldAttrId = $eavSetup->getAttributeId(Product::ENTITY, $oldAttrCode);
            if ($oldAttrId) {
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $oldAttrId,
                    [
                        'attribute_code' => $newAttrCode,
                    ]
                );
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

            $attributeConfig = $this->attributeConfig->getAttributesConfig();
            foreach ($attributeConfig as $attrCode => $config) {
                $removeAttributeFromGrid($attrCode);
            }
            $removeAttributeFromGrid('google_product_category');
        }

        // disable the extension for non-default stores
        if (version_compare($context->getVersion(), '2.0.0') < 0) {
            $this->systemConfig->disableExtensionForNonDefaultStores();

            // install per unit pricing attributes
            foreach ($this->attributeConfig->getUnitPriceAttributesConfig() as $attrCode => $config) {
                if (!$eavSetup->getAttributeId(Product::ENTITY, $attrCode)) {
                    $eavSetup->addAttribute(Product::ENTITY, $attrCode, $config);
                }
            }

            // create send_to_facebook & facebook_product_id product attributes
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
                ],
                'facebook_product_id' => [
                    'group' => 'Facebook Attribute Group',
                    'type' => 'varchar',
                    'label' => 'Facebook Product ID',
                    'input' => 'text',
                    'visible' => false,
                    'sort_order' => 10,
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

            //format facebook product attributes descriptions
            $attributeConfig = $this->attributeConfig->getAttributesConfig();
            foreach ($attributeConfig as $attrCode => $config) {
                if ($eavSetup->getAttributeId(Product::ENTITY, $attrCode) && isset($config['note'])) {
                    $eavSetup->updateAttribute(
                        Product::ENTITY,
                        $attrCode,
                        [
                            'note' => $config['note'],
                        ]
                    );
                }
            }
        }

        $setup->endSetup();
    }
}
