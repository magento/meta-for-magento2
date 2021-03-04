<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessProductAfterSaveEventObserver implements ObserverInterface
{
    const ATTR_CREATE = 'CREATE';

    /**
     * @var FBEHelper
     */
    protected $_fbeHelper;

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
        $this->_fbeHelper = $helper;
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
        $product = $observer->getEvent()->getProduct();
        if (!$product->getId()) {
            return;
        }

        try {
            $requestData = $this->batchApi->buildProductRequest($product);
            $requestParams = [];
            $requestParams[0] = $requestData;
            $response = $this->_fbeHelper->makeHttpRequest($requestParams, null);
        } catch (Exception $e) {
            $this->_fbeHelper->logException($e);
        }
    }
}
