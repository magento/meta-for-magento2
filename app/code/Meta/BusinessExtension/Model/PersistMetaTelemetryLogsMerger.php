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

use Magento\Framework\MessageQueue\MergerInterface;
use Magento\Framework\MessageQueue\MergedMessageInterface;

class PersistMetaTelemetryLogsMerger implements MergerInterface
{
    /**
     * Combine multiple queue messages into one
     *
     * @param object[] $messages
     * @return object[]|MergedMessageInterface[]
     */
    public function merge(array $messages)
    {
        if (isset($messages['persist.meta.telemetry.logs'])) {
            $telemetryLogs = array_map(function ($message) {
                $decodedMessage = json_decode($message, true);
                unset($decodedMessage['log_type']);
                unset($decodedMessage['store_id']);
                return $decodedMessage;
            }, $messages['persist.meta.telemetry.logs']);
            $mergedLogs = json_encode($telemetryLogs);
            return ['persist.meta.telemetry.logs' => [$mergedLogs]];
        }
        return $messages;
    }
}
