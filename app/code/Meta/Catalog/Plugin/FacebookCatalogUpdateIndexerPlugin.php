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
use Magento\Framework\Indexer\ActionInterface;

class FacebookCatalogUpdateIndexerPlugin
{
    /**
     * @var FBCatalogUpdateResourceModel
     */
    private $fbCatalogUpdateResourceModel;

    /**
     * FacebookCatalogUpdateOnIndexerPlugin constructor
     *
     * @param FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel
     */
    public function __construct(
        FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel
    ) {
        $this->fbCatalogUpdateResourceModel = $fbCatalogUpdateResourceModel;
    }

    /**
     *  Push product and child product updates to DB for meta update
     *
     * @param int[] $ids
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function createFacebookCatalogUpdates($ids)
    {
        $this->fbCatalogUpdateResourceModel->addProductsWithChildren($ids, 'update');
    }

    /**
     * After execute plugin
     *
     * @param ActionInterface $subject
     * @param void $result
     * @param int[] $ids
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(ActionInterface $subject, $result, array $ids)
    {
        $this->createFacebookCatalogUpdates($ids);
    }

    /**
     * After execute list plugin
     *
     * @param ActionInterface $subject
     * @param void $result
     * @param int[] $ids
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteList(ActionInterface $subject, $result, array $ids)
    {
        $this->createFacebookCatalogUpdates($ids);
    }

    /**
     * After execute row plugin
     *
     * @param ActionInterface $subject
     * @param void $result
     * @param int $id
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteRow(ActionInterface $subject, $result, $id)
    {
        $this->createFacebookCatalogUpdates([$id]);
    }
}
