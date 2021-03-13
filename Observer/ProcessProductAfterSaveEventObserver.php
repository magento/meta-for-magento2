<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
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
     * Constructor
     * @param FBEHelper $helper
     * @param BatchApi $batchApi
     */
    public function __construct(
        FBEHelper $helper,
        BatchApi $batchApi
    ) {
        $this->fbeHelper = $helper;
        $this->batchApi = $batchApi;
    }

    /**
     * Call an API to product save from facebook catalog
     * after save product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product->getId()) {
            return;
        }
        $storeId = $product->getStoreId();
        $product->setStoreId($this->fbeHelper->getStore()->getId());

        try {
            $requestData = $this->batchApi->buildProductRequest($product);
            $requestParams = [];
            $requestParams[0] = $requestData;
            $response = $this->fbeHelper->makeHttpRequest($requestParams, null);
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
        }

        $product->setStoreId($storeId);
    }
}
