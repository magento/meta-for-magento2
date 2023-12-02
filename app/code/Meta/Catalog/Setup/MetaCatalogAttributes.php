<?php

declare(strict_types=1);

namespace Meta\Catalog\Setup;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class MetaCatalogAttributes
{

    /**
     * Returns array of product attributes to be created
     *
     * @return array[]
     */
    public function getProductAttributes(): array
    {
        return [
            'google_product_category' => [
                'group' => 'General',
                'type' => 'varchar',
                'label' => 'Google Product Category',
                'input' => 'select',
                'source' => \Meta\Catalog\Model\Config\Source\Product\GoogleProductCategory::class,
                'required' => false,
                'sort_order' => 10,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'visible' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => false
            ],
            'send_to_facebook' => [
                'group' => 'General',
                'type' => 'int',
                'label' => 'Sync to Meta',
                'default' => 1,
                'source' => Boolean::class,
                'input' => 'select',
                'visible' => true,
                'sort_order' => 45,
                'required' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => false,
                'global' => ScopedAttributeInterface::SCOPE_STORE
            ]
        ];
    }

    /**
     * Returns array of category attributes to be created
     *
     * @return array[]
     */
    public function getCategoryAttributes(): array
    {
        return [
            SystemConfig::CATEGORY_SYNC_TO_FACEBOOK => [
                'type' => 'int',
                'label' => 'Sync to Meta',
                'input' => 'boolean',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'visible' => true,
                'default' => "1",
                'required' => false,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Display Settings'
            ]
        ];
    }

    /**
     * Returns array of meta product set id as category attribute to be created
     *
     * @return array[]
     */
    public function getCategoryProductSetIdAttribute(): array
    {
        return [
            SystemConfig::META_PRODUCT_SET_ID => [
                'type' => 'varchar',
                'label' => 'Meta Product Set Id',
                'input' => 'text',
                'source' => '',
                'required' => false,
                'default' => null,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
                'frontend' => '',
                'backend' => ''
            ]
        ];
    }
}
