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
use Magento\Framework\App\Request\Http;
use Meta\BusinessExtension\Api\CustomApiKey\UnauthorizedTokenException;

class Authenticator
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /** @var Http */
    private Http $httpRequest;

    /**
     * Authenticator constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $httpRequest
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Http $httpRequest)
    {
        $this->scopeConfig = $scopeConfig;
        $this->httpRequest = $httpRequest;
    }

    /**
     * Authenticate a given token against the stored API key
     *
     * @param string $token
     * @return void
     * @throws UnauthorizedTokenException
     */
    public function authenticate(string $token): void
    {
        $storedToken = $this->scopeConfig->getValue('meta_extension/general/api_key');
        if ($storedToken === null || $storedToken !== $token) {
            throw new UnauthorizedTokenException();
        }
    }

    /**
     * Authenticate a given token against the stored API key
     *
     * @return void
     * @throws UnauthorizedTokenException
     */
    public function authenticateRequest(): void
    {
        $receivedToken = $this->httpRequest->getHeader('Meta-extension-token');
        if ($receivedToken) {
            $this->authenticate($receivedToken);
        } else {
            throw new UnauthorizedTokenException();
        }
    }
}
