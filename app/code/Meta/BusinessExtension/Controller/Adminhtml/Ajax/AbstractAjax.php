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
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Security\Model\AdminSessionsManager;

abstract class AbstractAjax extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var FBEHelper
     */
    protected $_fbeHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
    }

    abstract protected function executeForJson();

    /**
     * @throws Exception
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        // TODO : Move all String objects to constants.
        $adminSession = $this->_fbeHelper
            ->createObject(AdminSessionsManager::class)
            ->getCurrentSession();
        if (!$adminSession && $adminSession->getStatus() != 1) {
            throw new Exception('Oops, this endpoint is for logged in admin and ajax only!');
        } else {
            try {
                $json = $this->executeForJson();
                return $result->setData($json);
            } catch (Exception $e) {
                $this->_fbeHelper->logCritical($e->getMessage());
                throw new Exception(
                    'Oops, there was error while processing your request.' .
                    ' Please contact admin for more details.'
                );
            }
        }
    }
}
