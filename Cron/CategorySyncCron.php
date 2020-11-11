<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;

class CategorySyncCron{

    protected $_fbeHelper;
    /**
     * @var CategoryCollection
     */
    protected $_category_collection;

    public function __construct(
        \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
        CategoryCollection $categoryCollection
    ){
        $this->_fbeHelper = $fbeHelper;
        $this->_category_collection = $categoryCollection;
    }

    public function execute(){
        $this->_fbeHelper->log('start category sync cron job ' );
        $this->_category_collection->pushAllCategoriesToFbCollections();
        return null;
    }
}
