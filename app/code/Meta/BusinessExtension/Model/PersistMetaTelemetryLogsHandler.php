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

use Meta\BusinessExtension\Helper\GraphAPIAdapter;

class PersistMetaTelemetryLogsHandler
{

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * PersistMetaTelemetryLogsHandler constructor
     *
     * @param GraphAPIAdapter $graphAPIAdapter
     */
    public function __construct(
        GraphAPIAdapter $graphAPIAdapter
    ) {
        $this->graphApiAdapter = $graphAPIAdapter;
    }

    /**
     * Consumer handler to persist telemetry logs from message queue to Meta
     *
     * @param string $messages
     */
    public function persistMetaTelemetryLogs(string $messages)
    {
        $telemetryContext = [];
        $telemetryContext['event'] = 'persist_meta_telemetry_logs';
        $telemetryContext['extra_data']['telemetry_logs'] = $messages;
        $this->graphApiAdapter->persistLogToMeta($telemetryContext);
    }
}
