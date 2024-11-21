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
use Magento\Framework\Mview\View;
use Magento\Indexer\Model\WorkingStateProvider;
use Magento\Framework\ObjectManagerInterface;

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
     * @var WorkingStateProvider
     */
    private $workingStateProvider;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * FacebookCatalogUpdateOnIndexerPlugin constructor
     *
     * @param WorkingStateProvider $workingStateProvider
     * @param FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        WorkingStateProvider         $workingStateProvider,
        FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel,
        ObjectManagerInterface  $objectManager
    ) {
        $this->workingStateProvider = $workingStateProvider;
        $this->fbCatalogUpdateResourceModel = $fbCatalogUpdateResourceModel;
        $this->objectManager = $objectManager;
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
            $walker = $this->getChangelogBatchWalkerInstance();
            $batchIds = $walker->walk($cl, $currentVersionId, $nextVersionId, $batchSize);
            /** Magento v2.4.7 and above, the walk function returns "yield" instead of an array */
            if (is_array($batchIds)) {
                if (empty($batchIds)) {
                    break;
                }
                $currentVersionId += $batchSize;
                $this->addProductsWithChildren($batchIds, 'update');
            } else {
                foreach ($batchIds as $ids) {
                    if (empty($ids)) {
                        break;
                    }
                    $this->addProductsWithChildren($ids, 'update');
                }
                $currentVersionId += $batchSize;
            }
        }
    }

    /**
     * Get class object
     */
    public function getChangelogBatchWalkerInstance()
    {
        $changeLogWalkerFactory = $this->objectManager->create(
            \Magento\Framework\Mview\View\ChangeLogBatchWalkerFactory::class // @phpstan-ignore-line
        );
        if (get_class($changeLogWalkerFactory) == "ChangeLogBatchWalkerFactory") {
            return $changeLogWalkerFactory->create(
                \Magento\Framework\Mview\View\ChangeLogBatchWalker::class // @phpstan-ignore-line
            );
        }

        $changelogWalkerFactory = $this->objectManager->create(
            \Magento\Framework\Mview\View\ChangelogBatchWalkerFactory::class // @phpstan-ignore-line
        );
        return $changelogWalkerFactory->create(
            \Magento\Framework\Mview\View\ChangelogBatchWalker::class // @phpstan-ignore-line
        );
    }

    /**
     * Add products with children
     *
     * @param array $batchesIds
     * @param string $method
     * @return int
     */
    private function addProductsWithChildren($batchesIds, $method)
    {
        return $this->fbCatalogUpdateResourceModel->addProductsWithChildren($batchesIds, $method);
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
