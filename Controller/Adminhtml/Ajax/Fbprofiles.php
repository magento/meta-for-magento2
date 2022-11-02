<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Fbprofiles extends AbstractAjax
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;
    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
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
        $old_profiles = $this->systemConfig->getConfig('fbprofile/ids');
        $response = [
        'success' => false,
        'profiles' => $old_profiles
        ];
        $profiles = $this->getRequest()->getParam('profiles');
        if ($profiles) {
            $this->systemConfig->saveConfig('fbprofile/ids', $profiles);
            $response['success'] = true;
            $response['profiles'] = $profiles;
            if ($old_profiles != $profiles) {
                $datetime = $this->_fbeHelper->createObject(DateTime::class);
                $this->systemConfig->saveConfig(
                    'fbprofiles/creation_time',
                    $datetime->gmtDate('Y-m-d H:i:s')
                );
            }
        }
        return $response;
    }
}
