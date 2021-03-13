<?php

/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Setup;

use Exception;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Facebook\BusinessExtension\Logger\Logger;
use Facebook\BusinessExtension\Model\Config\ProductAttributes;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Setup\UpgradeDataInterface;
use \Magento\Catalog\Model\Product;

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
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param CategorySetupFactory $categorySetupFactory
     * @param SetFactory $attributeSetFactory
     * @param ProductAttributes $attributeConfig
     * @param FBEHelper $helper
     * @param Logger $logger
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CategorySetupFactory $categorySetupFactory,
        SetFactory $attributeSetFactory,
        ProductAttributes $attributeConfig,
        FBEHelper $helper,
        Logger $logger
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeConfig = $attributeConfig;
        $this->helper = $helper;
        $this->logger = $logger;
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
        ModuleContextInterface $context
    ) {
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

        $setup->endSetup();
    }
}
