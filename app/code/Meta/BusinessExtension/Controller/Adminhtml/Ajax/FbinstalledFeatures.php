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
use JsonException;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;

class FbinstalledFeatures implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FacebookInstalledFeature
     */
    private $fbInstalledFeatureResource;

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
     * @param RequestInterface         $request
     * @param JsonFactory              $resultJsonFactory
     * @param FBEHelper                $fbeHelper
     * @param FacebookInstalledFeature $fbInstalledFeatureResource
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        FacebookInstalledFeature $fbInstalledFeatureResource
    ) {
        $this->request = $request;
        $this->fbInstalledFeatureResource = $fbInstalledFeatureResource;
        $this->jsonFactory = $resultJsonFactory;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Execute function
     *
     * @throws Exception
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $this->fbeHelper->checkAdminEndpointPermission();
        try {
            $json = $this->saveInstalledFeatures();
            return $result->setData($json);
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $this->request->getParam('storeId'),
                    'event' => 'fbe_installs',
                    'event_type' => 'save_installed_features'
                ]
            );
            throw new LocalizedException(
                __(
                    'There was error while processing your request.' .
                    ' Please contact admin for more details.'
                )
            );
        }
    }

    /**
     * Execute for json
     *
     * @return array
     * @throws JsonException
     */
    private function saveInstalledFeatures()
    {
        $storeId = $this->request->getParam('storeId');
        $response = [
            'success' => false
        ];
        $installedFeatures = $this->request->getParam('installed_features');
        if (!is_string($installedFeatures)) {
            return $response;
        } else {
            $installedFeaturesDecoded = json_decode($installedFeatures, true, 512, JSON_THROW_ON_ERROR);
            $this->fbInstalledFeatureResource->deleteAll($storeId);
            $this->fbInstalledFeatureResource->saveResponseData($installedFeaturesDecoded, $storeId);
            $response['success'] = true;
        }
        return $response;
    }
}
