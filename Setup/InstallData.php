<?php

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Setup;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Constructor
     *
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        SystemConfig $systemConfig
    ) {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @inheritDoc
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // disable the extension for non-default stores
        $this->systemConfig->disableExtensionForNonDefaultStores();
    }
}
