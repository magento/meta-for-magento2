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

namespace Meta\BusinessExtension\Controller\Adminhtml\ApiKey;

use Magento\Backend\App\Action;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\ResultFactory;
use Meta\BusinessExtension\Model\Api\CustomApiKey\KeyGenerator;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /**
     * @var KeyGenerator
     */
    protected $apiKeyGenerator;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Index constructor
     *
     * @param Action\Context $context
     * @param KeyGenerator $apiKeyGenerator
     * @param WriterInterface $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context  $context,
        KeyGenerator    $apiKeyGenerator,
        WriterInterface $configWriter,
        LoggerInterface $logger
    ) {
        $this->apiKeyGenerator = $apiKeyGenerator;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute the controller action to generate and save the API key
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->logger->info('API Key Controller was accessed.');

        if (!$this->_isAllowed()) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setHttpResponseCode(403);
        }

        $apiKey = $this->apiKeyGenerator->generate();
        $this->configWriter->save('meta_extension/general/api_key', $apiKey);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['key' => $apiKey]);
        return $resultJson;
    }

    /**
     * Check if the user has the required permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $this->logger->info('API Key Controller was attempted.');

        return $this->_authorization->isAllowed('Meta_BusinessExtension::generate_api_key');
    }
}
