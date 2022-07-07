<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Security\Model\AdminSessionsManager;

abstract class AbstractAjax extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var FBEHelper
     */
    protected $_fbeHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_fbeHelper = $fbeHelper;
    }

    abstract protected function executeForJson();

    /**
     * @throws Exception
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        // TODO : Move all String objects to constants.
        $adminSession = $this->_fbeHelper
            ->createObject(AdminSessionsManager::class)
            ->getCurrentSession();
        if (!$adminSession && $adminSession->getStatus() != 1) {
            throw new Exception('Oops, this endpoint is for logged in admin and ajax only!');
        } else {
            try {
                $json = $this->executeForJson();
                return $result->setData($json);
            } catch (Exception $e) {
                $this->_fbeHelper->logCritical($e->getMessage());
                throw new Exception(
                    'Oops, there was error while processing your request.' .
                    ' Please contact admin for more details.'
                );
            }
        }
    }
}
