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
use Magento\Framework\Filesystem\DirectoryList;

class AdobeCloudConfig implements AdobeCloudConfigInterface
{
    /**
     * List of files and directories unique to an Adobe Commerce Cloud deployment.
     * Their presence indicates the site is running on the cloud infrastructure.
     */
    public const CLOUD_FILES = [
        '.magento.app.yaml',
        '/vendor/magento/ece-tools/composer.json'
    ];

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * Class constructor
     *
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

    /**
     * Detect if the current seller's service is on hosted Adobe Cloud
     *
     * @return bool
     */
    public function isSellerOnAdobeCloud(): bool
    {
        $rootPath = $this->directoryList->getRoot();

        foreach (self::CLOUD_FILES as $file) {
            if (file_exists($rootPath . '/' . $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Call this method to get a string indicator on seller types (hosted by Adobe).
     *
     * @return string
     */
    public function getCommercePartnerSellerPlatformType(): string
    {
        return $this->isSellerOnAdobeCloud() ?
            AdobeCloudConfigInterface::ADOBE_COMMERCE_CLOUD : AdobeCloudConfigInterface::MAGENTO_OPEN_SOURCE;
    }
}
