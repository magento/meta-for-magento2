<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Uploader;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class ProductFeedUpload extends AbstractAjax
{
    /**
     * @var Uploader
     */
    protected $uploader;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        Uploader $uploader
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->uploader = $uploader;
    }

    public function executeForJson()
    {
        $response = [];

        if (!$this->_fbeHelper->getAccessToken()) {
            $response['success'] = false;
            $response['message'] = __('Set up the extension before uploading products.');
            return $response;
        }

        try {
            $feedPushResponse = $this->uploader->uploadFullCatalog();
            $response['success'] = true;
            $response['feed_push_response'] = $feedPushResponse;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
