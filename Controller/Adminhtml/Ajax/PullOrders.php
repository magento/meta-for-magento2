<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class PullOrders extends AbstractAjax
{
    /**
     * @var CommerceHelper
     */
    protected $commerceHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        CommerceHelper $commerceHelper
    )
    {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->commerceHelper = $commerceHelper;
    }

    public function executeForJson()
    {
        try {
            return ['success' => true, 'response' => $this->commerceHelper->pullPendingOrders()];
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->_fbeHelper->logException($e);
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }
}
