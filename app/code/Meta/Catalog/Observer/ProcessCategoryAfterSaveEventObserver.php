<?php

declare(strict_types=1);

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
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Feed\CategoryCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessCategoryAfterSaveEventObserver implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    private $_fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Constructor
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     */
    public function __construct(FBEHelper $helper, SystemConfig $systemConfig)
    {
        $this->_fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Execute observer for category save API call
     *
     * Call an API to category save from facebook catalog
     * after save category from Magento
     *
     * @param Observer $observer
     * @return string|void|null
     */
    public function execute(Observer $observer)
    {
        if (!$this->systemConfig->isCatalogSyncEnabled()) {
            return;
        }

        $category = $observer->getEvent()->getCategory();
        $this->_fbeHelper->log("save category: " . $category->getName());
        /** @var CategoryCollection $categoryObj */
        $categoryObj = $this->_fbeHelper->getObject(CategoryCollection::class);
        $syncEnabled = $category->getData("sync_to_facebook_catalog");
        if ($syncEnabled === "0") {
            $this->_fbeHelper->log("user disabled category sync");
            return;
        }

        return $categoryObj->makeHttpRequestAfterCategorySave($category);
    }
}
