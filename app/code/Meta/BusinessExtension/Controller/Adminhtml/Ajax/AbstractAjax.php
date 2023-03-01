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

use Exception;
use Magento\Backend\App\Action;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Action\HttpPostActionInterface;

abstract class AbstractAjax extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

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
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Execute for json
     *
     * @return array
     */
    abstract public function executeForJson();

    /**
     * Execute function
     *
     * @throws Exception
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        // TODO : Move all String objects to constants.
        $adminSession = $this->fbeHelper
            ->createObject(AdminSessionsManager::class)
            ->getCurrentSession();
        if (!$adminSession && $adminSession->getStatus() != 1) {
            throw new LocalizedException(__('Oops, this endpoint is for logged in admin and ajax only!'));
        } else {
            try {
                $json = $this->executeForJson();
                return $result->setData($json);
            } catch (Exception $e) {
                $this->fbeHelper->logCritical($e->getMessage());
                throw new LocalizedException(
                    __('Oops, there was error while processing your request.' .
                    ' Please contact admin for more details.')
                );
            }
        }
    }
}
