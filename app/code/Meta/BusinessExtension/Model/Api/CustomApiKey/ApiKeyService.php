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

namespace Meta\BusinessExtension\Model\Api\CustomApiKey;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Psr\Log\LoggerInterface;

/**
 * This class handles storage and retrieval for the CUSTOM api token we'll use to authenticate the Meta-specific
 * settings and management APIs we expose as part of the extension. It will not be used for any standard Magento APIs.
 */
class ApiKeyService
{
    /**
     * @var KeyGenerator
     */
    public $apiKeyGenerator;

    /**
     * @var WriterInterface
     */
    public $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param KeyGenerator         $apiKeyGenerator
     * @param WriterInterface      $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface      $logger
     */
    public function __construct(
        KeyGenerator         $apiKeyGenerator,
        WriterInterface      $configWriter,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger
    ) {
        $this->apiKeyGenerator = $apiKeyGenerator;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Get the existing Api key or generate and return it.
     *
     * @return string
     */
    public function getCustomApiKey(): string
    {
        $existingApiKey = $this->scopeConfig->getValue('meta_extension/general/api_key');
        if ($existingApiKey === null) {
            return $this->upsertApiKey();
        } else {
            return $existingApiKey;
        }
    }

    /**
     * Generate and save the API key if it doesn't already exist
     *
     * @return string
     */
    public function upsertApiKey(): string
    {
        $existingApiKey = $this->scopeConfig->getValue('meta_extension/general/api_key');
        if ($existingApiKey === null) {
            $this->logger->info('API key does not exist. Generating a new key.');
            $apiKey = $this->apiKeyGenerator->generate();
            $this->configWriter->save('meta_extension/general/api_key', $apiKey);
            $this->logger->info('API key has been generated and saved.');
            return $apiKey;
        } else {
            $this->logger->info('API key already exists. No action taken.');
            return $existingApiKey;
        }
    }
}
