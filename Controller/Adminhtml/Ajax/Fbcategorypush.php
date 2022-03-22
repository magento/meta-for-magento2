<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Facebook\BusinessExtension\Model\Feed\CategoryCollection;

class Fbcategorypush extends AbstractAjax
{

    private $_customerSession;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Facebook\BusinessExtension\Helper\FBEHelper $helper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context, $resultJsonFactory, $helper);
        $this->_customerSession = $customerSession;
    }

    public function executeForJson()
    {
        $response = [];
        try {
            $catalog_id = $this->getRequest()->getParam('catalogId');
            $category_obj = $this->_fbeHelper->getObject(CategoryCollection::class);
            $category_obj->saveCatalogId($catalog_id);
            $category_obj->pushAllCategoriesToFbCollections();
            $response['success'] = true;
            return $response;
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->_fbeHelper->logException($e);
            return $response;
        }
    }
}
