<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Product\Feed;

use Exception;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi as MethodBatchApi;

class Uploader
{
    /**
     * @var MethodBatchApi
     */
    protected $methodBatchApi;

    /**
     * @param MethodBatchApi $methodBatchApi
     */
    public function __construct(MethodBatchApi $methodBatchApi)
    {
        $this->methodBatchApi = $methodBatchApi;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function uploadFullCatalog()
    {
        return $this->methodBatchApi->generateProductRequestData();
    }
}
