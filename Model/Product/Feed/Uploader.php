<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Product\Feed;

use Facebook\BusinessExtension\Model\Config\Source\FeedUploadMethod;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi as MethodBatchApi;
use Facebook\BusinessExtension\Model\Product\Feed\Method\FeedApi as MethodFeedApi;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;

class Uploader
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var MethodBatchApi
     */
    protected $methodBatchApi;

    /**
     * @var MethodFeedApi
     */
    protected $methodFeedApi;

    /**
     * @param SystemConfig $systemConfig
     * @param MethodBatchApi $methodBatchApi
     * @param MethodFeedApi $methodFeedApi
     */
    public function __construct(
        SystemConfig $systemConfig,
        MethodBatchApi $methodBatchApi,
        MethodFeedApi $methodFeedApi
    )
    {
        $this->systemConfig = $systemConfig;
        $this->methodBatchApi = $methodBatchApi;
        $this->methodFeedApi = $methodFeedApi;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function uploadFullCatalog()
    {
        $uploadMethod = $this->systemConfig->getFeedUploadMethod();

        if ($uploadMethod === FeedUploadMethod::UPLOAD_METHOD_CATALOG_BATCH_API) {
            try {
                $response = $this->methodBatchApi->generateProductRequestData();
            } catch (\Exception $e) {
            }
        } else if ($uploadMethod === FeedUploadMethod::UPLOAD_METHOD_FEED_API) {
            $response = $this->methodFeedApi->execute();
        } else {
            throw new LocalizedException(__('Unknown feed upload method'));
        }
        return $response;
    }
}
