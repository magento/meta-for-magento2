<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\BusinessExtension\Controller\Adminhtml\Ajax;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
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
