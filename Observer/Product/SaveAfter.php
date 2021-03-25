<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Product;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var BatchApi
     */
    protected $batchApi;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @param SystemConfig $systemConfig
     * @param FBEHelper $helper
     * @param BatchApi $batchApi
     * @param GraphAPIAdapter $graphApiAdapter
     */
    public function __construct(
        SystemConfig $systemConfig,
        FBEHelper $helper,
        BatchApi $batchApi,
        GraphAPIAdapter $graphApiAdapter
    )
    {
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $helper;
        $this->batchApi = $batchApi;
        $this->graphApiAdapter = $graphApiAdapter;
    }

    /**
     * Call an API to product save from facebook catalog
     * after save product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveIncrementalProductUpdates())) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product->getId()) {
            return;
        }

        // @todo implement error handling/logging for invalid access token and other non-happy path scenarios
        // @todo implement batch API status check
        // @todo implement async call

        try {
            $requestData = $this->batchApi->buildProductRequest($product);
            $this->graphApiAdapter->catalogBatchRequest([$requestData]);
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
        }
    }
}
