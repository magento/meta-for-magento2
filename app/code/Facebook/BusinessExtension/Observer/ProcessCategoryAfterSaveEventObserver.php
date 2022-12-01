<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessCategoryAfterSaveEventObserver implements ObserverInterface
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
     * Call an API to category save from facebook catalog
     * after save category from Magento
     *
     * @param Observer $observer
     * @return
     */
    public function execute(Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        $this->_fbeHelper->log("save category: " . $category->getName());
        /** @var CategoryCollection $categoryObj */
        $categoryObj = $this->_fbeHelper->getObject(CategoryCollection::class);
        $syncEnabled =$category->getData("sync_to_facebook_catalog");
        if ($syncEnabled === "0") {
            $this->_fbeHelper->log("user disabled category sync");
            return;
        }

        $response = $categoryObj->makeHttpRequestAfterCategorySave($category);
        return $response;
    }
}
