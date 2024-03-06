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

namespace Meta\BusinessExtension\Model\Api;

use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;

class AdobeCloudConfig implements AdobeCloudConfigInterface
{
    /**
     * Detect if the current seller's service is on hosted Adobe Cloud
     *
     * @return bool
     */
    public function isSellerOnAdobeCloud(): bool
    {
        // Need to grab the env var in someway, and getenv() function also generates linter error
        // phpcs:ignore
        return isset($_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
    }
}
