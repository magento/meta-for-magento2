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

namespace Meta\Conversion\Controller\Pixel;

use Exception;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

class UserData implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var AAMFieldsExtractorHelper
     */
    private AAMFieldsExtractorHelper $aamFieldsExtractorHelper;

    /**
     * Constructor
     *
     * @param JsonFactory $jsonFactory
     * @param FBEHelper $fbeHelper
     * @param CustomerSession $customerSession
     * @param AAMFieldsExtractorHelper $aamFieldsExtractorHelper
     */
    public function __construct(
        JsonFactory $jsonFactory,
        FBEHelper $fbeHelper,
        CustomerSession $customerSession,
        AAMFieldsExtractorHelper $aamFieldsExtractorHelper
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->fbeHelper = $fbeHelper;
        $this->customerSession = $customerSession;
        $this->aamFieldsExtractorHelper = $aamFieldsExtractorHelper;
    }

    /**
     * Get logged in customer
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customerSession->isLoggedIn() ? $this->customerSession->getCustomer() : null;
    }

    /**
     * Get user data
     *
     * @return array|null
     */
    private function getUserData(): ?array
    {
        $userData = $this->aamFieldsExtractorHelper->getNormalizedUserData($this->getCustomer());
        return empty($userData) ? null : $userData;
    }

    /**
     * Get user data
     *
     * @return Json
     */
    public function execute(): Json
    {
        $response = [];
        try {
            $response = [
                'user_data' => $this->getUserData(),
                'success' => true,
            ];
        } catch (Exception $e) {
            $response['success'] = false;
            $this->fbeHelper->logException($e);
        }

        $responseJson = $this->jsonFactory->create();
        $responseJson->setData($response);

        return $responseJson;
    }
}
