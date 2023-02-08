<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Logger\Logger;
use Magento\Store\Model\StoreManagerInterface;

class UpdateConfigurations implements DataPatchInterface
{

    private const OLD_CONFIG_TABLE_NAME = 'facebook_business_extension_config';

    /**
     * @var SystemConfig
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SystemConfig $config
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SystemConfig $config,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Get dependencies for the Patch
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get alias for the Patch
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Apply the patch
     *
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $this->migrateOldConfigs($connection);
        $this->deleteOldConfigs($connection);

        $connection->endSetup();
    }

    /**
     * Revert the changes from Patch
     *
     * @return void
     */
    public function revert(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $connection->dropTable(self::OLD_CONFIG_TABLE_NAME);
        $connection->delete('core_config_data', "path LIKE 'facebook/%'");

        $connection->endSetup();
    }

    /**
     * Migrate configuration from old table to core_config_data
     *
     * @param $connection
     * @return void
     */
    private function migrateOldConfigs($connection): void
    {
        $facebookConfig = $connection->getTableName(self::OLD_CONFIG_TABLE_NAME);
        if ($facebookConfig) {
            $configKeys = [SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN => 'fbaccess/token',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED => 'fbe/installed',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID => 'fbe/external/id',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID => 'fbe/catalog/id',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS => 'fbpixel/aam_settings',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID => 'fbpixel/id',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES => 'fbprofile/ids',
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION => 'fb/api/version'
            ];

            $defaultStoreId = $this->storeManager->getDefaultStoreView()->getStoreGroupId();

            foreach ($configKeys as $newKey => $oldKey) {
                try {
                    $query = $connection->select()
                        ->from($facebookConfig, ['config_value'])
                        ->where('config_key = ?', $oldKey);
                    $value = $connection->fetchOne($query);
                    $this->config->saveConfig($newKey, $value, $defaultStoreId);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    $this->logger->critical('Error migrating: '. $oldKey);
                }
            }
        }
    }

    /**
     * Remove configurations from old table
     *
     * @param $connection
     * @return void
     */
    private function deleteOldConfigs($connection): void
    {
        $facebookConfig = $connection->getTableName(self::OLD_CONFIG_TABLE_NAME);
        if($facebookConfig) {
            try {
                $connection->delete($facebookConfig, "config_key NOT LIKE 'permanent%'");
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
