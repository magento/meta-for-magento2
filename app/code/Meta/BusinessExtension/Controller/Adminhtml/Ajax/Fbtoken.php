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

use Magento\Framework\Stdlib\DateTime\DateTime;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class Fbtoken extends AbstractAjax
{
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
            if ($oldAccessToken && $oldAccessToken != $accessToken) {
                $this->_fbeHelper->log('Updated Access token...');
            }
        }
        return $response;
    }
}
