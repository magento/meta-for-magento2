<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class CategorySyncCron
{

    protected $_fbeHelper;
    /**
     * @var CategoryCollection
     */
    protected $_category_collection;
    /**
     * @var SystemConfig
     */
    protected $_system_config;

    public function __construct(
        \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
        CategoryCollection $categoryCollection,
        SystemConfig $systemConfig
    ) {
        $this->_fbeHelper = $fbeHelper;
        $this->_category_collection = $categoryCollection;
        $this->_system_config = $systemConfig;
    }

    public function execute()
    {
        if ($this->_system_config->isActiveCollectionsSync() == true) {
            $this->_fbeHelper->log('start category sync cron job ');
            $this->_category_collection->pushAllCategoriesToFbCollections();
            return true;
        }
        return false;
    }
}
