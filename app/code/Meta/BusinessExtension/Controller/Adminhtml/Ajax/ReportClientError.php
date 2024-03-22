<?php

declare(strict_types=1);

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
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class ReportClientError extends AbstractAjax
{
    private const JS_EXCEPTION_CODE = 100;

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
        Context      $context,
        JsonFactory  $resultJsonFactory,
        FBEHelper    $fbeHelper
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * @inheritDoc
     */
    public function executeForJson()
    {
        $storeID = $this->getRequest()->getParam('storeID');
        $message = $this->getRequest()->getParam('message');
        $stackTrace = $this->getRequest()->getParam('stackTrace');

        $filename = $this->getRequest()->getParam('filename');
        $lineNumber = $this->getRequest()->getParam('line');
        $columnNumber = $this->getRequest()->getParam('column');

        $loggingContext = [
            'event' => 'js_exception',
            'event_type' => 'error',
            'extra_data' => [
                'filename' => $filename,
                'line_number' => $lineNumber,
                'column_number' => $columnNumber,
            ],
            'store_id' => $storeID,
        ];

        $this->fbeHelper->logExceptionDetailsImmediatelyToMeta(
            ReportClientError::JS_EXCEPTION_CODE,
            $message,
            $stackTrace,
            $loggingContext,
        );
        return [];
    }
}
