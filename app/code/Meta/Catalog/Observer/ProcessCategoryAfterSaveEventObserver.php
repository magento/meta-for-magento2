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
use Meta\Catalog\Model\Feed\CategoryCollection;

class ProcessCategoryAfterSaveEventObserver implements ObserverInterface
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
     * Execute observer for category save API call
     *
     * Call an API to category save from facebook catalog
     * after save category from Magento
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();

        $isNameChanged = $category->dataHasChangedFor('name');

        // we only pass category name and products ids to meta, so ignoring all other changes
        if ($isNameChanged
            || !empty($category->getAffectedProductIds())
            || $category->dataHasChangedFor('image')
            || $category->dataHasChangedFor('request_path')
            || $category->dataHasChangedFor('url_key')
        ) {
            $this->fbeHelper->log("save category: " . $category->getName());
            try {
                $this->categoryCollection->makeHttpRequestsAfterCategorySave(
                    $category,
                    $isNameChanged
                );
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage(
                    'Failed to update Meta for one or more stores. Please see Exception log for more detail.'
                );
                $this->fbeHelper->log(sprintf(
                    "Error occurred while updating category: %s , id: %s",
                    $category->getName(),
                    $category->getId()
                ));

                $this->fbeHelper->logException($e);
            }
        }
    }
}
