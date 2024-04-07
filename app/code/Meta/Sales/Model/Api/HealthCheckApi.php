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

namespace Meta\Sales\Model\Api;

use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\HealthCheckApiInterface;
use Meta\Sales\Helper\OrderHelper;

class HealthCheckApi implements HealthCheckApiInterface
{
    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @param Authenticator $authenticator
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Authenticator $authenticator,
        OrderHelper   $orderHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Health check for the Magento Dynamic Checkout API
     *
     * @param string $externalBusinessId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function healthCheck(string $externalBusinessId): bool
    {
        $this->authenticator->authenticateRequest();
        $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);

        return true;
    }
}
