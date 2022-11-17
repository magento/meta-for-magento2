<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Product\Feed;

use Magento\Catalog\Api\Data\ProductInterface;

interface ProductRetrieverInterface
{
    /**
     * @param $storeId
     * @return ProductRetrieverInterface
     */
    public function setStoreId($storeId);

    /**
     * @return string
     */
    public function getProductType();

    /**
     * @param int $offset
     * @param int $limit
     * @return ProductInterface[]
     */
    public function retrieve($offset = 1, $limit = 100): array;

    /**
     * @return int
     */
    public function getLimit();
}
