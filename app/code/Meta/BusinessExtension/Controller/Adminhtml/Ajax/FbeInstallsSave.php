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
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\MBEInstalls;

class FbeInstallsSave implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var MBEInstalls
     */
    private $saveFbeInstallsResponse;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * Construct
     *
     * @param RequestInterface $request
     * @param JsonFactory      $resultJsonFactory
     * @param FBEHelper        $fbeHelper
     * @param MBEInstalls      $saveFBEInstallsResponse
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        MBEInstalls $saveFBEInstallsResponse
    ) {
        $this->request = $request;
        $this->jsonFactory = $resultJsonFactory;
        $this->fbeHelper = $fbeHelper;
        $this->saveFbeInstallsResponse = $saveFBEInstallsResponse;
    }

    /**
     * Execute function
     *
     * @throws Exception
     */
    public function execute()
    {
        try {
            $result = $this->jsonFactory->create();
            $this->fbeHelper->checkAdminEndpointPermission();
            $json = $this->saveFbeInstalls();
            return $result->setData($json);
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $this->request->getParam('storeId'),
                    'event' => 'fbe_installs',
                    'event_type' => 'save_config'
                ]
            );
            throw new LocalizedException(
                __(
                    'The was an error while saving FbeInstalls config.' .
                    ' Please contact admin for more details.'
                )
            );
        }
    }

    /**
     * Execute for json
     *
     * @return array
     */
    public function saveFbeInstalls()
    {
        $data = $this->request->getParam('data');
        $storeId = $this->request->getParam('storeId');
        if (empty($storeId)) {
            $this->fbeHelper->logCritical('Could not save FbeInstalls config. No storeId found.');
            return [
                'success' => false,
                'message' => 'There was an issue saving FbeInstalls config.'
            ];
        }
        try {
            $success = $this->saveFbeInstallsResponse->save($data, $storeId);
            return ["success" => $success];
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'fbe_installs',
                    'event_type' => 'save_config'
                ]
            );
            return [
                'success' => false,
                'message' => 'There was an issue saving FbeInstalls config.'
            ];
        }
    }
}
