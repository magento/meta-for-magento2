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

namespace Meta\BusinessExtension\Model;

use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class PersistMetaTelemetryLogsHandler
{

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * PersistMetaTelemetryLogsHandler constructor
     *
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param SystemConfig    $systemConfig
     */
    public function __construct(
        GraphAPIAdapter $graphAPIAdapter,
        SystemConfig $systemConfig
    ) {
        $this->graphApiAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Consumer handler to persist telemetry logs from message queue to Meta
     *
     * @param  string $messages
     * @throws GuzzleException
     */
    public function persistMetaTelemetryLogs(string $messages)
    {
        $logs = json_decode($messages, true);
        $accessToken = null;
        foreach ($logs as $log) {
            if (isset($log['store_id'])) {
                $accessToken = $this->systemConfig->getAccessToken($log['store_id']);
                if ($accessToken) {
                    break;
                }
            }
        }
        $telemetryContext = [];
        $telemetryContext['event'] = FBEHelper::PERSIST_META_TELEMETRY_LOGS;
        $telemetryContext['extra_data'] = [];
        $telemetryContext['extra_data']['telemetry_logs'] = $messages;
        $this->graphApiAdapter->persistLogToMeta($telemetryContext, $accessToken);
    }
}
