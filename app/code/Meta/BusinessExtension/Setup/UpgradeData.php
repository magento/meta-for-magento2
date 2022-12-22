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

namespace Meta\BusinessExtension\Setup;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Logger\Logger;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    private const OLD_CONFIG_TABLE_NAME = 'facebook_business_extension_config';
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

        if (version_compare($context->getVersion(), '2.0.0') < 0) {
            // disable the extension for non-default stores
            $this->systemConfig->disableExtensionForNonDefaultStores();

            //migrate old configs to core_config_data table and delete old configs
            $this->migrateOldConfigs($eavSetup);
            $this->deleteOldConfigs($eavSetup);
        }

        $setup->endSetup();
    }

    private function migrateOldConfigs(EavSetup $eavSetup)
    {
        $facebookConfig = $eavSetup->getSetup()->getTable(self::OLD_CONFIG_TABLE_NAME);
        $configKeys = [SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN => 'fbaccess/token',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED => 'fbe/installed',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID => 'fbe/external/id',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID => 'fbe/catalog/id',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS => 'fbpixel/aam_settings',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID => 'fbpixel/id',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES => 'fbprofile/ids',
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION => 'fb/api/version'
        ];
        $connection = $eavSetup->getSetup()->getConnection();
        foreach ($configKeys as $newKey => $oldKey) {
            try {
                $query = $connection->select()
                    ->from($facebookConfig, ['config_value'])
                    ->where('config_key = ?', $oldKey);
                $value = $connection->fetchOne($query);
                $this->systemConfig->saveConfig($newKey, $value);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->logger->critical('Error migrating: '. $oldKey);
            }
        }
    }

    private function deleteOldConfigs(EavSetup $eavSetup)
    {
        $connection = $eavSetup->getSetup()->getConnection();
        $facebookConfig = $eavSetup->getSetup()->getTable(self::OLD_CONFIG_TABLE_NAME);
        try {
            $connection->delete($facebookConfig, "config_key NOT LIKE 'permanent%'");
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
