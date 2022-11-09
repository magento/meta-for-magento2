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
        }
        return $response;
    }
}
