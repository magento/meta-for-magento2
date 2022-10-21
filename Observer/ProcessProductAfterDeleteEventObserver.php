<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Helper\Context;

class ProcessProductAfterDeleteEventObserver implements ObserverInterface
{
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
     * Call an API to product delete from facebook catalog
     * after delete product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($product->getId()) {
            $requestData = [];
            $requestData['method'] = 'DELETE';
            $requestData['retailer_id'] = $product->getSku();
            $requestParams = [];
            $requestParams[0] = $requestData;
            $response = $this->_fbeHelper->makeHttpRequest($requestParams, null);
        }
    }
}
