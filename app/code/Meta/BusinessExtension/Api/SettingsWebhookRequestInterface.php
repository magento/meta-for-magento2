<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Api;

use Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface;

interface SettingsWebhookRequestInterface
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
     * Notification Getter
     *
     * @return ?\Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface
     */
    public function getNotification(): ?MetaIssueNotificationInterface;

    /**
     * Notification Setter
     *
     * @param \Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface $notification
     * @return void
     */
    public function setNotification(MetaIssueNotificationInterface $notification): void;

    /**
     * GraphApiVersion Getter
     *
     * @return null|string
     */
    public function getGraphApiVersion(): ?string;

    /**
     * GraphApiVersion Setter
     *
     * @param null|string $graphApiVersion
     * @return void
     */
    public function setGraphApiVersion(?string $graphApiVersion): void;
}
