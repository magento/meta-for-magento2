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

namespace Meta\BusinessExtension\Api;

interface AdobeCloudConfigInterface
{
    /**
     * Sellers who are using Adobe Commerce and have service hosted by Adobe Cloud
     */
    public const ADOBE_COMMERCE_CLOUD = "ADOBE_COMMERCE_CLOUD";

    /**
     * Sellers who are using Adobe Commerce but have services hosted on premise
     */
    public const ADOBE_COMMERCE_ON_PREMISE = "ADOBE_COMMERCE_ON_PREMISE";

    /**
     * Sellers who are using Magento Open Source
     */
    public const MAGENTO_OPEN_SOURCE = "MAGENTO_OPEN_SOURCE";

    /**
     * Detect if the current seller's service is hosted Adobe Cloud
     *
     * @return bool
     */
    public function isSellerOnAdobeCloud(): bool;

    /**
     * Call this method to get a string indicator on seller types (hosted by Adobe).
     *
     * @return string
     */
    public function getCommercePartnerSellerPlatformType(): string;
}
