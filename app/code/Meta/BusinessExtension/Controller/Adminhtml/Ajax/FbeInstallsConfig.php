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

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;

/**
 * Get FBE Install Configuration
 */
class FbeInstallsConfig implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var string
     */
    private string $endpoint = "";

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * Construct
     *
     * @param RequestInterface $request
     * @param FBEHelper $fbeHelper
     * @param JsonFactory $jsonFactory
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     */
    public function __construct(
        RequestInterface $request,
        FBEHelper $fbeHelper,
        JsonFactory $jsonFactory,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphAPIAdapter
    ) {
        $this->request = $request;
        $this->fbeHelper = $fbeHelper;
        $this->jsonFactory = $jsonFactory;
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
    }

    /**
     * Execute function
     *
     * @return ResponseInterface|Json|ResultInterface|void
     */
    public function execute()
    {
        try {
            $result = $this->jsonFactory->create();
            $this->fbeHelper->checkAdminEndpointPermission();
            $json = json_encode([]);
            $this->endpoint = $this->getFBEInstallsEndpoint();
            if (!$this->endpoint) {
                $this->fbeHelper->logCritical('Could not get MBEInstalls Config. No endpoint found.' .
                    ' Check MBE API version config.');
            } else {
                $json = $this->getFbeInstallsConfig();
            }
            return $result->setData($json);
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $this->request->getParam('storeId'),
                    'event' => 'fbe_installs',
                    'event_type' => 'get_config'
                ]
            );
        }
    }

    /**
     * Execute
     *
     * @return array
     */
    public function getFbeInstallsConfig()
    {
        $storeId = $this->request->getParam('storeId');
        $response = [
            'endpoint' => $this->endpoint,
            'externalBusinessId' => $this->systemConfig->getExternalBusinessId($storeId, ScopeInterface::SCOPE_STORES),
            'accessToken' => $this->systemConfig->getAccessToken($storeId)
        ];
        return $response;
    }

    /**
     * Get the endpoint URL for the 'fbeInstalls' API
     *
     * @return string|null
     */
    private function getFBEInstallsEndpoint()
    {
        if ($this->endpoint !== "") {
            return $this->endpoint;
        }
        $version = $this->graphAPIAdapter->getGraphApiVersion();
        if (!$version) {
            return null;
        }
        $baseUrl = $this->fbeHelper->getGraphBaseURL();
        $this->endpoint = "{$baseUrl}/{$version}/fbe_business/fbe_installs";
        return $this->endpoint;
    }
}
