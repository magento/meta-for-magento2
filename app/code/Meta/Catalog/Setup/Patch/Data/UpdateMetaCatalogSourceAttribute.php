<?php

namespace Meta\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateMetaCatalogSourceAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

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
     * Update the attribute source model value from Facebook to Meta
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->updateAttribute();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Update the attribute source model value from Facebook to Meta
     */
    private function updateAttribute()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('eav_attribute');
        // phpcs:disable
        $oldSourceModel = 'Facebook\BusinessExtension\Model\Config\Source\Product\GoogleProductCategory';
        $newSourceModel = \Meta\Catalog\Model\Config\Source\Product\GoogleProductCategory::class;

        $connection->update(
            $tableName,
            ['source_model' => $newSourceModel],
            $connection->quoteInto('source_model = ?', $oldSourceModel)
        );
    }

    /**
     * Revert the attribute source model value from Facebook to Meta
     *
     * @return void
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->revertAttributeUpdate();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Revert the attribute source model value from Facebook to Meta
     */
    private function revertAttributeUpdate()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('eav_attribute');
        // phpcs:disable
        $oldSourceModel = 'Facebook\BusinessExtension\Model\Config\Source\Product\GoogleProductCategory';
        $newSourceModel = \Meta\Catalog\Model\Config\Source\Product\GoogleProductCategory::class;
        $connection->update(
            $tableName,
            ['source_model' => $oldSourceModel],
            $connection->quoteInto('source_model = ?', $newSourceModel)
        );
    }
}
