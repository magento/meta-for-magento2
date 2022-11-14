<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class CategoryUpload extends AbstractAjax
{
    /**
     * @var CategoryCollection
     */
    protected $categoryCollection;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param CategoryCollection $categoryCollection
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        CategoryCollection $categoryCollection
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper, $systemConfig);
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @return array
     */
    public function executeForJson()
    {
        $response = [];

        if (!$this->systemConfig->getAccessToken()) {
            $response['success'] = false;
            $response['message'] = __('Before uploading categories, set up the extension.');
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
