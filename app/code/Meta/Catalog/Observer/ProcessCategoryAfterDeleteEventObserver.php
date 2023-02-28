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

use Meta\Catalog\Model\Feed\CategoryCollection;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessCategoryAfterDeleteEventObserver implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Constructor
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $helper,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Execute observer for category delete API call
     *
     * Call an API to category delete from facebook catalog
     * after delete category from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->systemConfig->isActiveIncrementalProductUpdates()) {
            return;
        }
        $category = $observer->getEvent()->getCategory();
        $this->fbeHelper->log("delete category: " . $category->getName());
        /** @var CategoryCollection $categoryObj */
        $categoryObj = $this->fbeHelper->getObject(CategoryCollection::class);
        $categoryObj->deleteCategoryAndSubCategoryFromFB($category);
    }
}
