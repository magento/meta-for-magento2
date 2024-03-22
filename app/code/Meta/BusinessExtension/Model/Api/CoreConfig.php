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

use Magento\Framework\DataObject;
use Meta\BusinessExtension\Api\CoreConfigInterface;

class CoreConfig extends DataObject implements CoreConfigInterface
{
    /**
     * Getter
     *
     * @return string
     */
    public function getExternalBusinessId(): string
    {
        return $this->_getData(self::DATA_EXTERNAL_BUSINESS_ID);
    }

    /**
     * Setter
     *
     * @param string $externalBusinessId
     * @return void
     */
    public function setExternalBusinessId(string $externalBusinessId): void
    {
        $this->setData(self::DATA_EXTERNAL_BUSINESS_ID, $externalBusinessId);
    }

    /**
     * Getter
     *
     * @return bool
     */
    public function isOrderSyncEnabled(): bool
    {
        return $this->_getData('isOrderSyncEnabled');
    }

    /**
     * IsOrderSyncEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsOrderSyncEnabled(bool $val): void
    {
        $this->setData('isOrderSyncEnabled', $val);
    }

    /**
     * CatalogSyncEnabled Getter
     *
     * @return bool
     */
    public function isCatalogSyncEnabled(): bool
    {
        return $this->_getData('isCatalogSyncEnabled');
    }

    /**
     * CatalogSyncEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsCatalogSyncEnabled(bool $val): void
    {
        $this->setData('isCatalogSyncEnabled', $val);
    }

    /**
     * IsPromotionsSyncEnabled Getter
     *
     * @return bool
     */
    public function isPromotionsSyncEnabled(): bool
    {
        return $this->_getData('isPromotionsSyncEnabled');
    }

    /**
     * IsPromotionsSyncEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsPromotionsSyncEnabled(bool $val): void
    {
        $this->setData('isPromotionsSyncEnabled', $val);
    }

    /**
     * ProductIdentifierAttr Getter
     *
     * @return string
     */
    public function getProductIdentifierAttr(): string
    {
        return $this->_getData('productIdentifierAttr');
    }

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setProductIdentifierAttr(?string $val): void
    {
        $this->setData('productIdentifierAttr', $val);
    }

    /**
     * ProductIdentifierAttr Getter
     *
     * @return ?string
     */
    public function getOutOfStockThreshold(): ?string
    {
        return $this->_getData('outOfStockThreshold');
    }

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setOutOfStockThreshold(?string $val): void
    {
        $this->setData('outOfStockThreshold', $val);
    }

    /**
     * FeedId Getter
     *
     * @return ?string
     */
    public function getFeedId(): ?string
    {
        return $this->_getData('feedId');
    }

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setFeedId(?string $val): void
    {
        $this->setData('feedId', $val);
    }

    /**
     * InstalledMetaExtensionVersion Getter
     *
     * @return ?string
     */
    public function getInstalledMetaExtensionVersion(): ?string
    {
        return $this->_getData('installedMetaExtensionVersion');
    }

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setInstalledMetaExtensionVersion(?string $val): void
    {
        $this->setData('installedMetaExtensionVersion', $val);
    }

    /**
     * GraphApiVersion Getter
     *
     * @return ?string
     */
    public function getGraphApiVersion(): ?string
    {
        return $this->_getData('graphApiVersion');
    }

    /**
     * GraphApiVersion Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setGraphApiVersion(?string $val): void
    {
        $this->setData('graphApiVersion', $val);
    }

    /**
     * MagentoVersion Getter
     *
     * @return ?string
     */
    public function getMagentoVersion(): ?string
    {
        return $this->_getData('magentoVersion');
    }

    /**
     * MagentoVersion Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setMagentoVersion(?string $val): void
    {
        $this->setData('magentoVersion', $val);
    }
}
