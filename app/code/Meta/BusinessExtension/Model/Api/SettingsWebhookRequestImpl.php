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
use Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface;
use Meta\BusinessExtension\Api\SettingsWebhookRequestInterface;

class SettingsWebhookRequestImpl extends DataObject implements SettingsWebhookRequestInterface
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
     * @param  string $externalBusinessId
     * @return void
     */
    public function setExternalBusinessId(string $externalBusinessId): void
    {
        $this->setData(self::DATA_EXTERNAL_BUSINESS_ID, $externalBusinessId);
    }

    /**
     * Notification Getter
     *
     * @return ?\Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface
     */
    public function getNotification(): ?MetaIssueNotificationInterface
    {
        return $this->_getData('notification');
    }

    /**
     * Notification Setter
     *
     * @param  \Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface $notification
     * @return void
     */
    public function setNotification(MetaIssueNotificationInterface $notification): void
    {
        $this->setData('notification', $notification);
    }

    /**
     * GraphApiVersion Setter
     *
     * @param  null|string $graphApiVersion
     * @return void
     */
    public function setGraphAPIVersion(?string $graphApiVersion): void
    {
        $this->setData('graphApiVersion', $graphApiVersion);
    }

    /**
     * GraphApiVersion Getter
     *
     * @return null|string
     */
    public function getGraphAPIVersion(): ?string
    {
        return $this->_getData('graphApiVersion');
    }
}
