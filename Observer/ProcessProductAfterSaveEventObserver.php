<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessProductAfterSaveEventObserver implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var BatchApi
     */
    protected $batchApi;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Constructor
     * @param FBEHelper $helper
     * @param BatchApi $batchApi
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper    $helper,
        BatchApi     $batchApi,
        SystemConfig $systemConfig
    )
    {
        $this->fbeHelper = $helper;
        $this->batchApi = $batchApi;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Call an API to product save from facebook catalog
     * after save product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->systemConfig->isActiveIncrementalProductUpdates()) {
            return;
        }
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product->getId()) {
            return;
        }
        $storeId = $product->getStoreId();
        $product->setStoreId($this->fbeHelper->getStore()->getId());

        try {
            $requestData = $this->batchApi->buildRequestForIndividualProduct($product);
            $requestParams = [];
            $requestParams[0] = $requestData;
            $this->fbeHelper->makeHttpRequest($requestParams, null);
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
        }

        $product->setStoreId($storeId);
    }
}
