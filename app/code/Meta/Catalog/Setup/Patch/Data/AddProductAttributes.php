<?php

declare(strict_types=1);

namespace Meta\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Meta\Catalog\Setup\MetaCatalogAttributes;

class AddProductAttributes implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var MetaCatalogAttributes
     */
    private $metaCatalogAttributes;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param MetaCatalogAttributes $metaCatalogAttributes
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        MetaCatalogAttributes $metaCatalogAttributes
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->metaCatalogAttributes = $metaCatalogAttributes;
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
     * Create product attributes
     *
     * @return void
     */
    public function apply(): void
    {
        $productAttributes = $this->metaCatalogAttributes->getProductAttributes();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);

        foreach ($productAttributes as $attributeCode => $attributeData) {

            if (!$eavSetup->getAttributeId(Product::ENTITY, $attributeCode)) {
                $eavSetup->addAttribute(Product::ENTITY, $attributeCode, $attributeData);
            }
            // Assign attributes to default attribute set
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $attributeData['group'],
                $attributeCode
            );
        }
    }

    /**
     * Revert the created product attributes
     *
     * @return void
     */
    public function revert(): void
    {
        $productAttributes = $this->metaCatalogAttributes->getProductAttributes();
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (array_keys($productAttributes) as $attributeCode) {
            $eavSetup->removeAttribute(Product::ENTITY, $attributeCode);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
