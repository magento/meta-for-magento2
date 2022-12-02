<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Promotion\Feed;

use Exception;
use Facebook\BusinessExtension\Model\Promotion\Feed\Method\FeedApi as MethodFeedApi;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class Uploader
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var MethodFeedApi
     */
    protected $methodFeedApi;

    /**
     * @param SystemConfig $systemConfig
     * @param MethodFeedApi $methodFeedApi
     */
    public function __construct(
        SystemConfig  $systemConfig,
        MethodFeedApi $methodFeedApi
    )
    {
        $this->systemConfig = $systemConfig;
        $this->methodFeedApi = $methodFeedApi;
    }

    /**
     * Upload Magento promotions to Facebook
     *
     * @param null $storeId
     * @return array
     * @throws Exception
     */
    public function uploadPromotions($storeId = null)
    {
        return $this->methodFeedApi->execute($storeId);
    }
}
