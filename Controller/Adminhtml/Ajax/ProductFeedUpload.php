<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Uploader;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class ProductFeedUpload extends AbstractAjax
{
    /**
     * @var Uploader
     */
    protected $uploader;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        Uploader $uploader
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper, $systemConfig);
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function executeForJson()
    {
        $response = [];

        // get default store info
        $storeId = $this->_fbeHelper->getStore()->getId();
        $storeName = $this->_fbeHelper->getStore()->getName();

        // override store if user switched config scope to non-default
        $storeParam = $this->getRequest()->getParam('store');
        if ($storeParam) {
            $storeId = $storeParam;
            $storeName = $this->systemConfig->getStoreManager()->getStore($storeId)->getName();
        }

        if (!$this->systemConfig->getAccessToken()) {
            $response['success'] = false;
            $response['message'] = __(sprintf('Before uploading products, set up the extension for \'%s\'.', $storeName));
            return $response;
        }

        try {
            $feedPushResponse = $this->uploader->uploadFullCatalog($storeId);
            $response['success'] = true;
            $response['feed_push_response'] = $feedPushResponse;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
