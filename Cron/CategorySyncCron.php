<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class CategorySyncCron
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var CategoryCollection
     */
    protected $categoryCollection;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * CategorySyncCron constructor
     *
     * @param FBEHelper $fbeHelper
     * @param CategoryCollection $categoryCollection
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $fbeHelper,
        CategoryCollection $categoryCollection,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->categoryCollection = $categoryCollection;
        $this->systemConfig = $systemConfig;
    }

    public function execute()
    {
        if ($this->systemConfig->isActiveCollectionsSync()) {
            $this->categoryCollection->pushAllCategoriesToFbCollections();
            return true;
        }
        return false;
    }
}
