<?php
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
namespace Meta\Catalog\Model\Product\Feed;

use Magento\Catalog\Api\Data\ProductInterface;

interface ProductRetrieverInterface
{
    /**
     * Set store Id
     *
     * @param int $storeId
     * @return ProductRetrieverInterface
     */
    public function setStoreId($storeId);

    /**
     * Get product type
     *
     * @return string
     */
    public function getProductType();

    /**
     * Retrieve products
     *
     * @param int $offset
     * @param int $limit
     * @return ProductInterface[]
     */
    public function retrieve($offset = 1, $limit = 100): array;

    /**
     * Get product limit
     *
     * @return int
     */
    public function getLimit();
}
