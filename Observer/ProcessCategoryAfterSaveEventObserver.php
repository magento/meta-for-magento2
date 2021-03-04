<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessCategoryAfterSaveEventObserver implements ObserverInterface
{
    const ATTR_CREATE = 'CREATE';

    /**
     * @var FBEHelper
     */
    protected $_fbeHelper;

    /**
     * Constructor
     * @param FBEHelper $helper
     */
    public function __construct(
        FBEHelper $helper
    ) {
        $this->_fbeHelper = $helper;
    }

    /**
     * Call an API to category save from facebook catalog
     * after save category from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        $this->_fbeHelper->log("save category: ".$category->getName());

        $categoryObj = $this->_fbeHelper->getObject(CategoryCollection::class);
        $reponse = $categoryObj->makeHttpRequestAfterCategorySave($category);
        return $reponse;
    }
}
