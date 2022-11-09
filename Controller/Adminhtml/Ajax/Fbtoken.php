<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class Fbtoken extends AbstractAjax
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->systemConfig = $systemConfig;
    }

    public function executeForJson()
    {
        $oldAccessToken = $this->systemConfig->getAccessToken();
        $response = [
            'success' => false,
            'accessToken' => $oldAccessToken
        ];
        $accessToken = $this->getRequest()->getParam('accessToken');
        if ($accessToken) {
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $accessToken);
            $response['success'] = true;
            $response['accessToken'] = $accessToken;
            if ($oldAccessToken != $accessToken) {
                $this->_fbeHelper->log("Updated Access token...");
                $datetime = $this->_fbeHelper->createObject(DateTime::class);
                $this->systemConfig->saveConfig(
                    SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN_CREATION_TIME,
                    $datetime->gmtDate('Y-m-d H:i:s')
                );
            }
        }
        return $response;
    }
}
