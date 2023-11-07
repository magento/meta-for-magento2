<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Api;

interface SettingsWebhookListenerInterface
{
    /**
     * Process settings POST request
     *
     * @param SettingsWebhookRequestInterface[] $settingsWebhookRequest
     * @return void
     */
    public function processSettingsWebhookRequest(array $settingsWebhookRequest);

    /**
     * Process settings GET request
     *
     * @param string $externalBusinessId
     * @return CoreConfigInterface
     */
    public function getCoreConfig(string $externalBusinessId):CoreConfigInterface;
}
