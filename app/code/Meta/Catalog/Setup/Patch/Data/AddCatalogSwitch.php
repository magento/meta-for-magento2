<?php

declare(strict_types=1);

namespace Meta\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class AddCatalogSwitch implements DataPatchInterface
{
    private const CORE_CONFIG_TABLE = "core_config_data";
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;
    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SystemConfig $systemConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->systemConfig = $systemConfig;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $stores = $this->systemConfig
            ->getStoreManager()
            ->getStores(false, true);

        // update for default config as well
        $this->updateStoreCatalogIntegration(0);
        foreach ($stores as $store) {
            $this->updateStoreCatalogIntegration($store->getId());
        }

        $connection->endSetup();
    }

    /**
     * Updates Store catalog integration
     *
     * @param $storeId
     * @return void
     */
    private function updateStoreCatalogIntegration($storeId): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $coreConfigTable = $connection->getTableName(self::CORE_CONFIG_TABLE);

        $isDailyFeedSyncEnabled = $this->fetchValue(
            $storeId,
            "facebook/catalog_management/daily_product_feed"
        );
        $isCatalogSyncEnabled = $this->fetchValue(
            $storeId,
            "facebook/catalog_management/enable_catalog_sync"
        );
        $outOfStockThresholdOld = $this->fetchValue(
            $storeId,
            "facebook/inventory_management/out_of_stock_threshold"
        );
        $outOfStockThresholdNew = $this->fetchValue(
            $storeId,
            "facebook/catalog_management/out_of_stock_threshold"
        );

        if ($isCatalogSyncEnabled == null && $isDailyFeedSyncEnabled != null) {
            $connection->insert($coreConfigTable, [
                "scope" => $storeId ? "stores" : "default",
                "scope_id" => $storeId,
                "path" => "facebook/catalog_management/enable_catalog_sync",
                "value" => $isDailyFeedSyncEnabled,
            ]);
        }

        if ($outOfStockThresholdNew == null && $outOfStockThresholdOld != null) {
            $connection->insert($coreConfigTable, [
                "scope" => $storeId ? "stores" : "default",
                "scope_id" => $storeId,
                "path" => "facebook/catalog_management/out_of_stock_threshold",
                "value" => $outOfStockThresholdOld,
            ]);
        }

        $connection->delete($coreConfigTable, [
            "scope_id = ?" => $storeId,
            "path = ?" => "facebook/catalog_management/daily_product_feed",
        ]);
        $connection->delete($coreConfigTable, [
            "scope_id = ?" => $storeId,
            "path = ?" =>
                "facebook/inventory_management/out_of_stock_threshold",
        ]);
        $connection->delete($coreConfigTable, [
            "scope_id = ?" => $storeId,
            "path = ?" =>
                "facebook/catalog_management/incremental_product_updates",
        ]);
        $connection->delete($coreConfigTable, [
            "scope_id = ?" => $storeId,
            "path = ?" =>
                "facebook/inventory_management/enable_inventory_upload",
        ]);
        $connection->delete($coreConfigTable, [
            "scope_id = ?" => $storeId,
            "path = ?" => "facebook/catalog_management/feed_upload_method",
        ]);
    }

    /**
     * Fetch store config value
     *
     * @param $storeId
     * @param $config_path
     * @return mixed|null
     */
    private function fetchValue($storeId, $config_path): mixed
    {
        $connection = $this->moduleDataSetup->getConnection();
        $scopeCondition = $connection->prepareSqlCondition("scope_id", [
            "eq" => $storeId,
        ]);
        $pathCondition = $connection->prepareSqlCondition("path", [
            "eq" => $config_path,
        ]);
        $query = $connection
            ->select()
            ->from($connection->getTableName(self::CORE_CONFIG_TABLE))
            ->where($scopeCondition)
            ->where($pathCondition);

        $result = $connection->fetchRow($query);

        return $result ? $result['value'] : null;
    }
}
