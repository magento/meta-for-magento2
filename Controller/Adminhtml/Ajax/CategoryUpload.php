<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class CategoryUpload extends AbstractAjax
{
    /**
     * @var CategoryCollection
     */
    protected $categoryCollection;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        CategoryCollection $categoryCollection
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->categoryCollection = $categoryCollection;
    }

    public function executeForJson()
    {
        $response = [];

        if (!$this->_fbeHelper->getAccessToken()) {
            $response['success'] = false;
            $response['message'] = __('Set up the extension before uploading category.');
            return $response;
        }

        try {
            $feedPushResponse = $this->categoryCollection->pushAllCategoriesToFbCollections();
            $response['success'] = true;
            $response['feed_push_response'] = $feedPushResponse;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
