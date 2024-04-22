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
use Meta\BusinessExtension\Helper\FBEHelper;
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
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * Authenticator constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Http                 $httpRequest
     * @param SystemConfig         $systemConfig
     * @param FBEHelper            $fbeHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http                 $httpRequest,
        SystemConfig         $systemConfig,
        FBEHelper            $fbeHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->httpRequest = $httpRequest;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Authenticate an API request (validate the token and RSA signature)
     *
     * @param string|null $storeId
     * @return void
     * @throws LocalizedException
     */
    public function authenticateRequest(?string $storeId = null): void
    {
        $this->authenticateToken();
        $this->authenticateSignature($storeId);
    }

    /**
     * Authenticate an API request (validate the token only)
     *
     * @return void
     * @throws LocalizedException
     */
    public function authenticateRequestDangerouslySkipSignatureValidation(): void
    {
        $this->authenticateToken();
    }

    /**
     * Authenticate token against the stored API key
     *
     * @return void
     * @throws LocalizedException
     */
    private function authenticateToken(): void
    {
        $receivedToken = $this->httpRequest->getHeader('Meta-extension-token');
        if ($receivedToken) {
            $storedToken = $this->scopeConfig->getValue('meta_extension/general/api_key');
            if ($storedToken === null || $storedToken !== $receivedToken) {
                throw new LocalizedException(__('Unauthorized Token'));
            }
        } else {
            throw new LocalizedException(__('Missing Meta Extension Token'));
        }
    }

    /**
     * Authenticate RSA Signature for API Request
     *
     * @param string|null $storeId
     * @return void
     * @throws LocalizedException
     */
    private function authenticateSignature(?string $storeId = null): void
    {
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
            $ex = new LocalizedException(__('RSA Signature Validation Failed'));
            if ($storeId !== null) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $ex,
                    [
                        'store_id' => $storeId,
                        'event' => 'authentication_error',
                        'event_type' => 'rsa_signature_validation_error',
                        'extra_data' => [
                            'request_uri' => $requestUri,
                            'request_body' => $requestBody,
                            'request_signature' => $signature
                        ]
                    ]
                );
            }
            throw $ex;
        }
    }
}
