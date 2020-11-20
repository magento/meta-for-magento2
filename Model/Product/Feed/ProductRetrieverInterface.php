<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Product\Feed;

use Magento\Catalog\Api\Data\ProductInterface;

interface ProductRetrieverInterface
{
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
