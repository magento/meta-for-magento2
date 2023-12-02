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

interface CoreConfigInterface
{
    public const DATA_EXTERNAL_BUSINESS_ID = 'externalBusinessId';

    /**
     * ExternalBusinessId Getter
     *
     * @return string
     */
    public function getExternalBusinessId(): string;

    /**
     * ExternalBusinessId Setter
     *
     * @param string $externalBusinessId
     * @return void
     */
    public function setExternalBusinessId(string $externalBusinessId): void;

    /**
     * IsOrderSyncEnabled Getter
     *
     * @return bool
     */
    public function isOrderSyncEnabled(): bool;

    /**
     * IsOrderSyncEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsOrderSyncEnabled(bool $val): void;

    /**
     * CatalogSyncEnabled Getter
     *
     * @return bool
     */
    public function isCatalogSyncEnabled(): bool;

    /**
     * CatalogSyncEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsCatalogSyncEnabled(bool $val): void;

    /**
     * IsOnsiteCheckoutEnabled Getter
     *
     * @return bool
     */
    public function isOnsiteCheckoutEnabled(): bool;

    /**
     * IsOnsiteCheckoutEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsOnsiteCheckoutEnabled(bool $val): void;

    /**
     * IsPromotionsSyncEnabled Getter
     *
     * @return bool
     */
    public function isPromotionsSyncEnabled(): bool;

    /**
     * IsPromotionsSyncEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsPromotionsSyncEnabled(bool $val): void;

    /**
     * ProductIdentifierAttr Getter
     *
     * @return ?string
     */
    public function getProductIdentifierAttr(): ?string;

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setProductIdentifierAttr(?string $val): void;

    /**
     * ProductIdentifierAttr Getter
     *
     * @return ?string
     */
    public function getOutOfStockThreshold(): ?string;

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setOutOfStockThreshold(?string $val): void;

    /**
     * IsCommerceExtensionEnabled Getter
     *
     * @return bool
     */
    public function isCommerceExtensionEnabled(): bool;

    /**
     * IsOnsiteCheckoutEnabled Setter
     *
     * @param bool $val
     * @return void
     */
    public function setIsCommerceExtensionEnabled(bool $val): void;

    /**
     * FeedId Getter
     *
     * @return ?string
     */
    public function getFeedId(): ?string;

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setFeedId(?string $val): void;

    /**
     * InstalledMetaExtensionVersion Getter
     *
     * @return ?string
     */
    public function getInstalledMetaExtensionVersion(): ?string;

    /**
     * ExternalBusinessId Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setInstalledMetaExtensionVersion(?string $val): void;

    /**
     * GraphApiVersion Getter
     *
     * @return ?string
     */
    public function getGraphApiVersion(): ?string;

    /**
     * GraphApiVersion Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setGraphApiVersion(?string $val): void;

    /**
     * MagentoVersion Getter
     *
     * @return ?string
     */
    public function getMagentoVersion(): ?string;

    /**
     * MagentoVersion Setter
     *
     * @param ?string $val
     * @return void
     */
    public function setMagentoVersion(?string $val): void;
}
