<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Config\App\Config\Type\System;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Fbprofiles extends AbstractAjax
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
        $oldProfiles = $this->systemConfig->getProfiles();
        $response = [
            'success' => false,
            'profiles' => $oldProfiles
        ];
        $profiles = $this->getRequest()->getParam('profiles');
        if ($profiles) {
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES, $profiles);
            $response['success'] = true;
            $response['profiles'] = $profiles;
            if ($oldProfiles != $profiles) {
                $datetime = $this->_fbeHelper->createObject(DateTime::class);
                $this->systemConfig->saveConfig(
                    SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES_CREATION_TIME,
                    $datetime->gmtDate('Y-m-d H:i:s')
                );
            }
        }
        return $response;
    }
}
