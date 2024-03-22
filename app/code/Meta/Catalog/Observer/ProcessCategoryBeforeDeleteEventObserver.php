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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Model\Category\CategoryCollection;

class ProcessCategoryBeforeDeleteEventObserver implements ObserverInterface
{
    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Constructor
     * @param FBEHelper $helper
     * @param CategoryCollection $categoryCollection
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        FBEHelper          $helper,
        CategoryCollection $categoryCollection,
        ManagerInterface   $messageManager
    ) {
        $this->fbeHelper = $helper;
        $this->categoryCollection = $categoryCollection;
        $this->messageManager = $messageManager;
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
        $category = $observer->getEvent()->getCategory();
        $category_name = $category->getName();
        $this->fbeHelper->log("delete category: " . $category_name);

        try {
            $this->categoryCollection->deleteCategoryAndSubCategoryFromFB($category);
        } catch (\Throwable $e) {
            $category_id = $category->getId();
            $this->messageManager->addErrorMessage(
                'Failed to delete Category from Meta for one or more stores.' .
                ' Please see Exception log for more detail.'
            );
            $this->fbeHelper->log(sprintf(
                "Error occurred while deleting category: %s , id: %s",
                $category_name,
                $category_id
            ));

            $this->fbeHelper->logException($e);
        }
    }
}
