<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer\Product;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class DeleteAfter implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     */
    public function __construct(SystemConfig $systemConfig, GraphApiAdapter $graphApiAdapter, FBEHelper $fbeHelper)
    {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Call an API to product delete from facebook catalog
     * after delete product from Magento
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

        // @todo observer should not know how to assemble request
        $requestData = [
            'method' => 'DELETE',
            'data' => ['id' => $this->fbeHelper->getRetailerId($product)],
        ];

        try {
            $storeId = $product->getStoreId();
            $catalogId = $this->systemConfig->getCatalogId($storeId);
            $this->graphApiAdapter->catalogBatchRequest($catalogId, [$requestData]);
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
        }
    }
}
