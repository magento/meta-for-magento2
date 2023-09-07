<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Api;

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
}
