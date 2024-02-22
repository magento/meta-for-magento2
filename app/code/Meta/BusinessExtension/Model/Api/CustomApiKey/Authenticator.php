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
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class Authenticator
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Http
     */
    private Http $httpRequest;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * Authenticator constructor
     *
     * @param ScopeConfigInterface  $scopeConfig
     * @param Http                  $httpRequest
     * @param SystemConfig          $systemConfig
     */
    public function __construct(
        ScopeConfigInterface    $scopeConfig,
        Http                    $httpRequest,
        SystemConfig            $systemConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->httpRequest = $httpRequest;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Authenticate a given token against the stored API key
     *
     * @param string $token
     * @return void
     * @throws LocalizedException
     */
    public function authenticate(string $token): void
    {
        $storedToken = $this->scopeConfig->getValue('meta_extension/general/api_key');
        if ($storedToken === null || $storedToken !== $token) {
            throw new LocalizedException(__('Unauthorized Token'));
        }
    }

    /**
     * Authenticate a given token against the stored API key
     *
     * @return void
     * @throws LocalizedException
     */
    public function authenticateRequest(): void
    {
        $receivedToken = $this->httpRequest->getHeader('Meta-extension-token');
        if ($receivedToken) {
            $this->authenticate($receivedToken);
        } else {
            throw new LocalizedException(__('Missing Meta Extension Token'));
        }
    }

    /**
     * Validate RSA Signature for API Request
     *
     * @return void
     * @throws LocalizedException
     */
    public function validateSignature(): void
    {
        if (!$this->systemConfig->isRsaSignatureValidationEnabled()) {
            return;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $publicKey = file_get_contents(__DIR__ . '/PublicKey.pem');
        $publicKeyResource = openssl_get_publickey($publicKey);
        if ($publicKeyResource == false) {
            throw new LocalizedException(__('Invalid Public Key'));
        }

        $signature = $this->httpRequest->getHeader('Rsa-Signature');
        if ($signature == false) {
            throw new LocalizedException(__('Missing RSA Signature'));
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $decodedSignature = base64_decode($signature);

        $requestUri = $this->httpRequest->getRequestUri();
        $requestBody = $this->httpRequest->getContent();
        $originalMessage = $requestUri . $requestBody;

        $verification = openssl_verify($originalMessage, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        if (!$verification) {
            throw new LocalizedException(__('RSA Signature Validation Failed'));
        }
    }
}
