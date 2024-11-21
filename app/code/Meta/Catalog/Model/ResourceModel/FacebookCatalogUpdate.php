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

namespace Meta\Catalog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Meta\Catalog\Model\ResourceModel\FacebookCatalogUpdate\CollectionFactory;
use Meta\Catalog\Model\ResourceModel\FacebookCatalogUpdate\Collection;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Math\Random;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\Stdlib\DateTime\DateTime;

class FacebookCatalogUpdate extends AbstractDb
{
    private const TABLE_NAME = 'facebook_catalog_update';

    public const BATCH_LIMIT = 5000;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Random
     */
    private $random;

    /**
     * @var OptionProvider
     */
    private $optionProvider;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * FacebookCatalogUpdate constructor
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Random $random
     * @param OptionProvider $optionProvider
     * @param DateTime $dateTime
     * @param string $connectionName
     */
    public function __construct(
        Context           $context,
        CollectionFactory $collectionFactory,
        Random            $random,
        OptionProvider    $optionProvider,
        DateTime          $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->collectionFactory = $collectionFactory;
        $this->random = $random;
        $this->optionProvider = $optionProvider;
        $this->dateTime = $dateTime;
    }

    /**
     * Construct
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }

    /**
     * Inserts array of product ids into table
     *
     * @param int[] $ids
     * @param string $method
     * @return int
     */
    public function addProductIds(array $ids, string $method): int
    {
        $existingProductIds = $this->getExistingProductIds($ids, $method);
        $dataToInsert = [];
        foreach ($ids as $id) {
            if (!isset($existingProductIds[$id])) {
                $dataToInsert[] = ['product_id' => $id, 'method' => $method];
            }
        }
        if (!empty($dataToInsert)) {
            $connection = $this->_resources->getConnection();

            $facebookCatalogUpdateTable = $this->_resources->getTableName(self::TABLE_NAME);
            return $connection->insertMultiple($facebookCatalogUpdateTable, $dataToInsert);
        }
        return 0;
    }

    /**
     * Get associative array of existing products in table
     *
     * @param int[] $ids
     * @param string $method
     * @return array
     */
    private function getExistingProductIds(array $ids, string $method): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', ['in' => $ids])
            ->addFieldToFilter('method', ['eq' => $method])
            ->addFieldToFilter('batch_id', ['null' => true]);
        $data = $collection->getData();
        return array_column($data, null, 'product_id');
    }

    /**
     * Set the batch_ids for a group of entities
     *
     * @param string $method
     * @param string $batchId
     * @return int
     */
    public function reserveProductsForBatchId(string $method, string $batchId): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('batch_id', ['null' => true]);
        $collection->addFieldToFilter('method', ['eq' => $method]);
        $collection->addFieldToSelect('row_id');
        $collection->setPageSize(self::BATCH_LIMIT);
        $data = $collection->getData();

        $connection = $this->_resources->getConnection();
        $updateWhere = $connection->quoteInto('row_id IN (?)', $data);

        $facebookCatalogUpdateTable = $this->_resources->getTableName(self::TABLE_NAME);
        return $connection->update($facebookCatalogUpdateTable, ['batch_id' => $batchId], $updateWhere);
    }

    /**
     * Delete entities matching batch_id
     *
     * @param string $batchId
     * @return int
     */
    public function deleteBatch(string $batchId): int
    {
        $connection = $this->_resources->getConnection();

        $facebookCatalogUpdateTable = $this->_resources->getTableName(self::TABLE_NAME);
        return $connection->delete($facebookCatalogUpdateTable, ['batch_id = ?' => $batchId]);
    }

    /**
     * Clear batch_id for batch group
     *
     * @param string $batchId
     * @return int
     */
    public function clearBatchId(string $batchId): int
    {
        $connection = $this->_resources->getConnection();

        $facebookCatalogUpdateTable = $this->_resources->getTableName(self::TABLE_NAME);
        return $connection->update($facebookCatalogUpdateTable, ['batch_id' => null], ['batch_id = ?' => $batchId]);
    }

    /**
     * Returns unique a batchId for product updates
     *
     * @return string
     */
    public function getUniqueBatchId(): string
    {
        $prefix = $this->random->getRandomString(2);
        return $this->random->getUniqueHash($prefix);
    }

    /**
     * Get reserved products from batch ID
     *
     * @param string $batchId
     * @return Collection
     */
    public function getReservedProducts(string $batchId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('batch_id', ['eq' => $batchId]);
        return $collection;
    }

    /**
     * Gets child product links to get child product ids for configurables
     *
     * @param array $parentIds
     * @return array
     */
    private function getChildProductLinks(array $parentIds)
    {
        $parentLinkField = $this->optionProvider->getProductEntityLinkField();
        $connection = $this->_resources->getConnection();

        $catalogProductSuperLinkTable = $this->_resources->getTableName('catalog_product_super_link');
        $catalogProductEntityTable = $this->_resources->getTableName('catalog_product_entity');

        $select = $connection->select()
            ->from($catalogProductSuperLinkTable, ['parent_id', 'product_id'])
            ->joinLeft(['e' => $catalogProductEntityTable], "e.{$parentLinkField} = parent_id")
            ->where('e.entity_id IN (?)', $parentIds);
        return $connection->fetchAll($select);
    }

    /**
     * Delete all the product update entries
     *
     * @param bool|int|string $sku
     * @return void
     */
    public function deleteUpdateProductEntries($sku): void
    {
        if ($sku === null) {
            return;
        }
        $connection = $this->_resources->getConnection();

        $facebookCatalogUpdateTable = $this->_resources->getTableName(self::TABLE_NAME);
        $connection->delete($facebookCatalogUpdateTable, ['sku = ?' => $sku, 'method = ?' => 'update']);
    }

    /**
     * Inserts all the products and child products into table from parentIds
     *
     * @param array $productIds
     * @param string $method
     * @return int
     */
    public function addProductsWithChildren(array $productIds, string $method)
    {
        $parentIds = [];
        $childIds = [];
        foreach ($this->getChildProductLinks($productIds) as $productLink) {
            if (!isset($parentIds[$productLink['entity_id']])) {
                $parentIds[$productLink['entity_id']] = 1;
            }
            $childIds[] = $productLink['product_id'];
        }
        $parentIdsToExclude = array_keys($parentIds); // Exclude parent configurable products from being updated
        $productIdsToSave = array_diff($productIds, $parentIdsToExclude);
        return $this->addProductIds($productIdsToSave, $method) + $this->addProductIds($childIds, $method);
    }

    /**
     * Cleanup table by deleting updated entities older than a week
     *
     * @return int
     */
    public function cleanupTable()
    {
        $dateLimit = $this->dateTime->date(null, '-7 days');
        $connection = $this->_resources->getConnection();

        $facebookCatalogUpdateTable = $this->_resources->getTableName(self::TABLE_NAME);
        return $connection->delete(
            $facebookCatalogUpdateTable,
            [
                "batch_id IS NOT NULL",
                "created_at < '{$dateLimit}'"
            ]
        );
    }
}
