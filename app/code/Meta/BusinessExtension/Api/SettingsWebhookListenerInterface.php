<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Api;

interface SettingsWebhookListenerInterface
{
    /**
     * Process settings request
     *
     * @param SettingsWebhookRequestInterface[] $settingsWebhookRequest
     * @return void
     */
    public function processSettingsWebhookRequest(array $settingsWebhookRequest);
}
