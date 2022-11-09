<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessProductAfterDeleteEventObserver implements ObserverInterface
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
        FBEHelper $helper,
        BatchApi $batchApi,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $helper;
        $this->batchApi = $batchApi;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Call an API to product delete from facebook catalog
     * after delete product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->systemConfig->isActiveIncrementalProductUpdates()) {
            return;
        }

        $product = $observer->getEvent()->getProduct();

        if ($product->getId()) {
            $requestData = [];
            $requestData['method'] = 'DELETE';
            $requestData['retailer_id'] = $this->fbeHelper->getRetailerId($product);
            $requestParams = [];
            $requestParams[0] = $requestData;
            $this->fbeHelper->makeHttpRequest($requestParams, null);
        }
    }
}
