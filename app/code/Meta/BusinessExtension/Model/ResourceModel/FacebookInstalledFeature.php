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

namespace Meta\BusinessExtension\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FacebookInstalledFeature extends AbstractDb
{
    private const TABLE_NAME = 'facebook_installed_features';

    /**
     * Construct
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(self::TABLE_NAME, 'row_id');
    }

    /**
     * Check if feature exists
     *
     * @param string $featureType
     * @param int $storeId
     * @return bool
     */
    public function doesFeatureTypeExist($featureType, $storeId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(self::TABLE_NAME)
            ->where('feature_type = ?', $featureType)
            ->where('store_id = ?', $storeId);
        return $connection->fetchRow($select) !== false;
    }

    /**
     * Delete all features matching storeId
     *
     * @param int $storeId
     * @return int
     */
    public function deleteAll($storeId)
    {
        $connection = $this->getConnection();
        return $connection->delete(self::TABLE_NAME, ['store_id = ?' => $storeId]);
    }
    
    /**
     * Save response data from 'installed_features' to table
     *
     * @param array $features
     * @param int $storeId
     * @return void
     */
    public function saveResponseData($features, $storeId)
    {
        $finalFeatures = array_map(
            function ($value) use ($storeId) {
                $temp = $value;
                $temp['store_id'] = $storeId;
                $temp['connected_assets'] = $this->formatArrayData($value, 'connected_assets');
                $temp['additional_info'] = $this->formatArrayData($value, 'additional_info');
                return $temp;
            },
            array_values($features)
        );
        
        $connection = $this->getConnection();
        $connection->insertOnDuplicate(self::TABLE_NAME, $finalFeatures);
    }

    /**
     * Format array data for insertion in DB
     *
     * @param array|string $feature
     * @param string $key
     * @return string|null
     */
    private function formatArrayData($feature, $key)
    {
        if (!isset($feature[$key])) {
            return null;
        }
        if (!is_array($feature[$key])) {
            return $feature[$key];
        }
        if (array_is_list($feature[$key]) && !empty($feature[$key])) { //If the value is a list save the first item
            return json_encode($feature[$key][0]);
        }
        return json_encode($feature[$key]);
    }
}
