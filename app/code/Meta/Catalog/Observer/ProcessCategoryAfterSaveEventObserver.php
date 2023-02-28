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

namespace Meta\Catalog\Observer;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Feed\CategoryCollection;
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
     * Execute observer for category save API call
     *
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
