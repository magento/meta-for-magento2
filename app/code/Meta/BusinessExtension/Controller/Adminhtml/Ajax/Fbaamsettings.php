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

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Helper\FBEHelper;

class Fbaamsettings extends AbstractAjax
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * Construct
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Execute for json
     *
     * @return array
     */
    public function executeForJson()
    {
        $response = [
            'success' => false,
            'settings' => null,
        ];
        $pixelId = $this->getRequest()->getParam('pixelId');
        $storeId = $this->getRequest()->getParam('storeId');
        if ($pixelId) {
            $settingsAsString = $this->fbeHelper->fetchAndSaveAAMSettings($pixelId, $storeId);
            if ($settingsAsString) {
                $response['success'] = true;
                $response['settings'] = $settingsAsString;
            }
        }
        return $response;
    }
}
