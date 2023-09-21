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

namespace Meta\Catalog\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\FullModuleList;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class LogCatalogSetup
{
    /**
     * @var GraphAPIAdapter
     */
    private $graphAPIAdapter;

    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var FullModuleList
     */
    private $fullModuleList;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var bool
     */
    private static $logInstalledModules = false;

    /**
     * @param GraphAPIAdapter    $graphAPIAdapter
     * @param SystemConfig       $systemConfig
     * @param FBEHelper          $fbeHelper
     * @param FullModuleList     $fullModuleList
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        GraphAPIAdapter $graphAPIAdapter,
        SystemConfig $systemConfig,
        FBEHelper $fbeHelper,
        FullModuleList $fullModuleList,
        ResourceConnection $resourceConnection
    ) {
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
        $this->fullModuleList = $fullModuleList;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute the CRON job
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getAllFBEInstalledStores() as $store) {
            try {
                $storeId = $store->getId();
                $this->graphAPIAdapter->persistLogToMeta([
                    'event' => 'log_catalog_setup_data',
                    'event_type' => 'log_catalog_setup',
                    'catalog_id' => $this->systemConfig->getCatalogId($storeId),
                    'commerce_merchant_settings_id' => $this->systemConfig->getCommerceAccountId($storeId),
                    'extra_data' => [
                        'items' => json_encode(
                            [
                                'item_count' => $this->queryItemCount(),
                                'group_count' => $this->queryGroupCount(),
                                'breakdown' => $this->queryBreakdown(),
                            ]
                        ),
                        'extensions' => self::$logInstalledModules
                                ? json_encode($this->fullModuleList->getAll())
                                : null
                            ]
                    ]);
            } catch (\Exception $e) {
                    $this->fbeHelper->logExceptionImmediatelyToMeta($e, [
                    'catalog_id' => $this->systemConfig->getCatalogId(),
                    'event' => 'log_catalog_setup_cron_exception',
                    'event_type' => 'log_catalog_setup'
                    ]);
            }
        }
    }

    /**
     * Queries the product count breakdown
     *
     * @return array
     */
    private function queryBreakdown()
    {
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $storeTable = $this->resourceConnection->getTableName('store');
        $attributes = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $sendToFacebookId = $this->queryAttributeId('send_to_facebook');
        $statusId = $this->queryAttributeId('status');
        $visibilityId = $this->queryAttributeId('visibility');

        $connection = $this->resourceConnection->getConnection();
        return $connection
            ->select()
            ->from(['store' => $storeTable], [])
            ->join(['product' => $productTable], "1 = 1", [])
            ->joinLeft(
                ['send_to_facebook' => $attributes],
                "product.entity_id = send_to_facebook.entity_id"
                    ." AND store.store_id = send_to_facebook.store_id"
                    ." AND send_to_facebook.attribute_id = {$sendToFacebookId}",
                []
            )
            ->joinLeft(
                ['status' => $attributes],
                "product.entity_id = status.entity_id"
                    ." AND store.store_id = status.store_id"
                    ." AND status.attribute_id = {$statusId}",
                []
            )
            ->joinLeft(
                ['visibility' => $attributes],
                "product.entity_id = visibility.entity_id"
                    ." AND store.store_id = visibility.store_id"
                    ." AND visibility.attribute_id = {$visibilityId}",
                []
            )
            ->group([
                'store.store_id',
                'store.name',
                'product.type_id',
                'status.value',
                'visibility.value',
                'send_to_facebook.value'
            ])
            ->columns([
                'store_id' => 'store.store_id',
                'store_name' => 'store.name',
                'type_id' => 'product.type_id',
                'status' => 'status.value',
                'visibility' => 'visibility.value',
                'send_to_facebook' => 'send_to_facebook.value',
                'count' => 'count(*)',
            ])
            ->query()
            ->fetchAll();
    }

    /**
     * Queries the number of product items
     *
     * @return int
     * @throws \Zend_Db_Statement_Exception
     */
    private function queryItemCount()
    {
        $productsTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $result = $this->resourceConnection
            ->getConnection()
            ->select()
            ->from($productsTable)
            ->columns('count(*) AS count')
            ->query()
            ->fetch();

        return $result['count'];
    }

    /**
     * Queries the number of product groups
     *
     * @return int
     * @throws \Zend_Db_Statement_Exception
     */
    private function queryGroupCount()
    {
        $productsTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $linksTable = $this->resourceConnection->getTableName('catalog_product_super_link');

        $result = $this->resourceConnection
            ->getConnection()
            ->select()
            ->from($productsTable, [])
            ->joinLeft(
                ['link' => $linksTable],
                'entity_id = link.product_id',
                []
            )
            ->where('link.product_id IS NULL')
            ->columns('count(*) AS count')
            ->query()
            ->fetch();

        return $result['count'];
    }

    /**
     * Queries the id of an attribute
     *
     * @param string $code
     * @return int
     * @throws \Zend_Db_Statement_Exception
     */
    private function queryAttributeId(string $code)
    {
        $attributesTable = $this->resourceConnection->getTableName('eav_attribute');

        $result = $this->resourceConnection->getConnection()->select()->from($attributesTable)
            ->columns('attribute_id')
            ->where('attribute_code = ?', $code)
            ->limit(1)
            ->query()
            ->fetch();

        return $result['attribute_id'];
    }
}
