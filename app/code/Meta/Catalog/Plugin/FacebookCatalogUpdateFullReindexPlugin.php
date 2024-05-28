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

namespace Meta\Catalog\Plugin;

use Meta\Catalog\Model\ResourceModel\FacebookCatalogUpdate as FBCatalogUpdateResourceModel;
use Magento\Indexer\Model\Indexer;
use Magento\Framework\Mview\View\ChangelogBatchWalker;
use Magento\Framework\Mview\View\ChangelogBatchWalkerFactory;
use Magento\Framework\Mview\View;
use Magento\Indexer\Model\WorkingStateProvider;

class FacebookCatalogUpdateFullReindexPlugin
{
    public const INDEXERS = [
        'catalogrule_product',
        'cataloginventory_stock'
    ];

    /**
     * @var FBCatalogUpdateResourceModel
     */
    private $fbCatalogUpdateResourceModel;

    /**
     * @var ChangelogBatchWalkerFactory
     */
    private $changelogBatchWalkerFactory;

    /**
     * @var WorkingStateProvider
     */
    private $workingStateProvider;

    /**
     * FacebookCatalogUpdateOnIndexerPlugin constructor
     *
     * @param WorkingStateProvider $workingStateProvider
     * @param FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel
     * @param ChangelogBatchWalkerFactory $changelogBatchWalkerFactory
     */
    public function __construct(
        WorkingStateProvider $workingStateProvider,
        FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel,
        ChangelogBatchWalkerFactory $changelogBatchWalkerFactory
    ) {
        $this->workingStateProvider = $workingStateProvider;
        $this->fbCatalogUpdateResourceModel = $fbCatalogUpdateResourceModel;
        $this->changelogBatchWalkerFactory = $changelogBatchWalkerFactory;
    }

    /**
     * Before reindex all plugin
     *
     * @param Indexer $subject
     * @return void
     */
    public function beforeReindexAll(Indexer $subject)
    {
        if (!$this->shouldSaveUpdates($subject)) {
            return;
        }

        $batchSize = View::DEFAULT_BATCH_SIZE;
        $view = $subject->getView();
        $cl = $view->getChangelog();
        $currentVersionId = (int)$view->getState()->getVersionId();
        $nextVersionId = $cl->getVersion();

        while ($currentVersionId < $nextVersionId) {
            $walker = $this->changeLogBatchWalkerFactory->create(ChangeLogBatchWalker::class);
            $ids = $walker->walk($cl, $currentVersionId, $nextVersionId, $batchSize);

            if (empty($ids)) {
                break;
            }
            $currentVersionId += $batchSize;
            $this->fbCatalogUpdateResourceModel->addProductsWithChildren($ids, 'update');
        }
    }

    /**
     * Checks to run the catalog update plugin
     *
     * @param Indexer $subject
     * @return bool
     */
    private function shouldSaveUpdates(Indexer $subject)
    {
        return (!$this->workingStateProvider->isWorking($subject->getId()))
            && in_array($subject->getId(), self::INDEXERS)
            && $subject->getView()->isEnabled();
    }
}
