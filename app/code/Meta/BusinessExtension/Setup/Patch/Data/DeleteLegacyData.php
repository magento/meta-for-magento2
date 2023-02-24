<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class DeleteLegacyData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Get dependencies for the data patch
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return  [];
    }

    /**
     * Get alias for the data patch
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Delete unnecessary data from the legacy version of the extension
     *
     * @return void
     */
    public function apply(): void
    {
        $productAttributesToDelete = [
            'facebook_age_group',
            'facebook_gender',
            'facebook_pattern',
            'facebook_decor_style',
            'facebook_color',
            'facebook_capacity',
            'facebook_material',
            'facebook_size',
            'facebook_style',
            'facebook_brand',
            'facebook_product_length',
            'facebook_product_width',
            'facebook_product_height',
            'facebook_model',
            'facebook_product_depth',
            'facebook_ingredients',
            'facebook_resolution',
            'facebook_age_range',
            'facebook_screen_size',
            'facebook_maximum_weight',
            'facebook_minimum_weight',
            'facebook_display_technology',
            'facebook_operating_system',
            'facebook_is_assembly_required',
            'facebook_storage_capacity',
            'facebook_number_of_licenses',
            'facebook_product_form',
            'facebook_compatible_devices',
            'facebook_video_game_platform',
            'facebook_system_requirements',
            'facebook_baby_food_stage',
            'facebook_recommended_use',
            'facebook_digital_zoom',
            'facebook_scent',
            'facebook_health_concern',
            'facebook_megapixels',
            'facebook_thread_count',
            'facebook_gemstone',
            'facebook_optical_zoom',
            'facebook_package_quantity',
            'facebook_shoe_width',
            'facebook_finish',
            'facebook_product_weight',
        ];

        $connection = $this->moduleDataSetup->getConnection();

        $connection->startSetup();

        // drop legacy facebook_business_extension_config table
        $connection->dropTable('facebook_business_extension_config');

        // delete legacy product attributes
        $eavAttributeTable = $connection->getTableName('eav_attribute');
        foreach ($productAttributesToDelete as $attributeCode) {
            $connection->delete($eavAttributeTable, ['attribute_code = ?' => $attributeCode]);
        }

        // delete legacy attribute group
        $eavAttributeGroupTable = $connection->getTableName('eav_attribute_group');
        $connection->delete($eavAttributeGroupTable, ['attribute_group_name = ?' => 'Facebook Attribute Group']);

        $connection->endSetup();
    }
}
