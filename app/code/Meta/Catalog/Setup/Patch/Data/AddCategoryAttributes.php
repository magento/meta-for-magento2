<?php

declare(strict_types=1);

namespace Meta\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Meta\Catalog\Setup\MetaCatalogAttributes;

class AddCategoryAttributes implements DataPatchInterface
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
     * Create category attributes
     *
     * @return void
     */
    public function apply(): void
    {
        $categoryAttributes = $this->metaCatalogAttributes->getCategoryAttributes();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach ($categoryAttributes as $attributeCode => $attributeData) {
            if (!$eavSetup->getAttributeId(Category::ENTITY, $attributeCode)) {
                $eavSetup->addAttribute(Category::ENTITY, $attributeCode, $attributeData);
            }
        }
    }

    /**
     * Revert the created product attributes
     *
     * @return void
     */
    public function revert(): void
    {
        $categoryAttributes = $this->metaCatalogAttributes->getCategoryAttributes();
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (array_keys($categoryAttributes) as $attributeCode) {
            $eavSetup->removeAttribute(Category::ENTITY, $attributeCode);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
