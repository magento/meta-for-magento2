<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;

class PersistConfiguration extends AbstractAjax
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
        try {
            $external_business_id = $this->getRequest()->getParam('externalBusinessId');
            $this->saveExternalBusinessId($external_business_id);
            $catalog_id = $this->getRequest()->getParam('catalogId');
            $this->saveCatalogId($catalog_id);
            $response['success'] = true;
            $response['feed_push_response'] = 'Business and catalog IDs successfully saved';

            return $response;
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
