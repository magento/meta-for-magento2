<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Helper\Context;

class ProcessCategoryAfterDeleteEventObserver implements ObserverInterface
{
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
     * Call an API to category delete from facebook catalog
     * after delete category from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        $this->_fbeHelper->log("delete category: ".$category->getName());
        $categoryObj = $this->_fbeHelper->getObject(CategoryCollection::class);
        $categoryObj->deleteCategoryAndSubCategoryFromFB($category);
    }
}
