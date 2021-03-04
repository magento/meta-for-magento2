<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;

class Fbfeedpush extends AbstractAjax
{

    /**
     * @var BatchApi
     */
    protected $batchApi;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Facebook\BusinessExtension\Helper\FBEHelper $helper,
        BatchApi $batchApi
    ) {
        parent::__construct($context, $resultJsonFactory, $helper);
        $this->batchApi = $batchApi;
    }

    public function executeForJson()
    {
        $response = [];
        $external_business_id = $this->_fbeHelper->getConfigValue('fbe/external/id');
        $this->_fbeHelper->log("Existing external business id --- ". $external_business_id);
        if ($external_business_id) {
            $response['success'] = false;
            $response['message'] = 'One time feed push is completed at the time of setup';
            return $response;
        }
        try {
            /* even the rest code failed, we should store external business id and catalog id,
            because user can push feed sync button in configuration*/
            $access_token = $this->getRequest()->getParam('accessToken');
            $external_business_id = $this->getRequest()->getParam('externalBusinessId');
            $this->saveExternalBusinessId($external_business_id);
            $catalog_id = $this->getRequest()->getParam('catalogId');
            $this->saveCatalogId($catalog_id);
            if ($access_token) {
                $feed_push_response = $this->batchApi->generateProductRequestData($access_token);
                $response['success'] = true;
                $response['feed_push_response'] = $feed_push_response;
                return $response;
            }
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->_fbeHelper->logException($e);
            return $response;
        }
    }

    public function saveCatalogId($catalog_id)
    {
        if ($catalog_id != null) {
            $this->_fbeHelper->saveConfig('fbe/catalog/id', $catalog_id);
            $this->_fbeHelper->log("Catalog id saved on instance --- ". $catalog_id);
        }
    }

    public function saveExternalBusinessId($external_business_id)
    {
        if ($external_business_id != null) {
            $this->_fbeHelper->saveConfig('fbe/external/id', $external_business_id);
            $this->_fbeHelper->log("External business id saved on instance --- ". $external_business_id);
        }
    }
}
