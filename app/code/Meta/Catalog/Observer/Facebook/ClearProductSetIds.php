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

namespace Meta\Catalog\Observer\Facebook;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Throwable;

class ClearProductSetIds implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param EavConfig $eavConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        FBEHelper $fbeHelper,
        EavConfig $eavConfig,
        ResourceConnection $resourceConnection
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->eavConfig = $eavConfig;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * It clears all the product set id for all the categories for given store
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $storeId = $observer->getEvent()->getStoreId();
        try {
            $productSetAttribute = $this->eavConfig->getAttribute(
                Category::ENTITY,
                SystemConfig::META_PRODUCT_SET_ID
            );
            $productSetAttributeId = $productSetAttribute->getAttributeId();

            if ($productSetAttributeId) {
                $categoryEntityVarcharTable = $this->resourceConnection->getTableName(
                    'catalog_category_entity_varchar'
                );
                $this->resourceConnection->getConnection()->delete($categoryEntityVarcharTable, [
                    'attribute_id = ?' => $productSetAttributeId,
                    'store_id = ?' => $storeId,
                ]);
            }
        } catch (Throwable $e) {
            $context = [
                'store_id' => $storeId,
                'event' => 'update_catalog_config',
                'event_type' => 'clear_product_set_ids',
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
        }
    }
}
