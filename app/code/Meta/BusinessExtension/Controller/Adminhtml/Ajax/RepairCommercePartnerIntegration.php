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
use Psr\Log\LoggerInterface;

class RepairCommercePartnerIntegration implements HttpPostActionInterface
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
     * @var MBEInstalls
     */
    private $mbeInstalls;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param MBEInstalls $mbeInstalls
     * @param RequestInterface $request
     */
    public function __construct(
        JsonFactory      $resultJsonFactory,
        FBEHelper        $fbeHelper,
        MBEInstalls      $mbeInstalls,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fbeHelper = $fbeHelper;
        $this->mbeInstalls = $mbeInstalls;
        $this->request = $request;
    }

    /**
     * Execute function
     *
     * @throws Exception
     */
    public function execute()
    {
        try {
            $result = $this->resultJsonFactory->create();
            $this->fbeHelper->checkAdminEndpointPermission();
            $json = $this->repairCommercePartnerIntegration();
            return $result->setData($json);
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $this->request->getParam('storeId'),
                    'event' => 'repair_cpi_fail',
                    'event_type' => 'save_config'
                ]
            );
            throw new LocalizedException(
                __('The was an error while trying to repair Meta Commerce Partner Integration.' .
                    ' Please contact admin for more details.')
            );
        }
    }

    /**
     * Execute function for repairCommercePartnerIntegration
     *
     * @throws Exception
     */
    public function repairCommercePartnerIntegration()
    {
        $storeId = $this->request->getParam('storeId');
        if (empty($storeId)) {
            return [
                'success' => false,
            ];
        }
        $this->mbeInstalls->repairCommercePartnerIntegration($storeId);
        return [
            'success' => true,
        ];
    }
}
