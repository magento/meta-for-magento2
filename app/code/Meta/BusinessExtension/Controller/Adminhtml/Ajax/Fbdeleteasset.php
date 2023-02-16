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

class Fbdeleteasset extends AbstractAjax
{
    const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Meta Business Extension.
    The pixel installed on your website is now deleted.";

    const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
      Please try again.";

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
     * @return array
     */
    public function executeForJson()
    {
        try {
            $this->fbeHelper->deleteConfigKeys();
            $response = [
                'success' => true,
                'message' => __(self::DELETE_SUCCESS_MESSAGE),
            ];
        } catch (\Exception $e) {
            $this->fbeHelper->log($e->getMessage());
            $response = [
                'success' => false,
                'error_message' => __(self::DELETE_FAILURE_MESSAGE),
            ];
        }
        return $response;
    }
}
