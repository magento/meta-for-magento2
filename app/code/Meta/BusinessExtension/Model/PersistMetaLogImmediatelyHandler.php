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
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class PersistMetaLogImmediatelyHandler
{
    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * PersistMetaLogImmediatelyHandler constructor
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
     * Consumer handler to persist exception logs from message queue to Meta
     *
     * @param  string $message
     * @return void
     */
    public function persistMetaLogImmediately(string $message): void
    {
        $context = json_decode($message, true);
        $accessToken = null;
        if (isset($context['store_id'])) {
            $accessToken = $this->systemConfig->getAccessToken($context['store_id']);
        }
        // Meta expects extra_data field to be dict<string, string>
        $context['extra_data'] = array_map(
            function ($value) {
                return is_string($value) ? $value : json_encode($value);
            }, $context['extra_data']
        );
        $this->graphApiAdapter->persistLogToMeta($context, $accessToken);
    }
}
