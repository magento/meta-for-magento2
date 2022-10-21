<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Model\Feed\ProductFeed;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessProductAfterSaveEventObserver implements ObserverInterface
{
    const ATTR_CREATE = 'CREATE';

    /**
     * @var \Facebook\BusinessExtension\Helper\FBEHelper
     */
    protected $_fbeHelper;

    /**
     * Constructor
     * @param \Facebook\BusinessExtension\Helper\FBEHelper $helper
     */
    public function __construct(
        \Facebook\BusinessExtension\Helper\FBEHelper $helper)
    {
        $this->_fbeHelper = $helper;
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
        if ($product->getId()) {
            /** @var ProductFeed $feedObj */
            $feedObj = $this->_fbeHelper->getObject(ProductFeed::class);
            $requestData = $feedObj->buildProductRequest($product);
            $requestParams = [];
            $requestParams[0] = $requestData;
            $response = $this->_fbeHelper->makeHttpRequest($requestParams, null);
        }
    }
}
