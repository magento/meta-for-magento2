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
use Magento\Framework\MessageQueue\EnvelopeInterface;

class PersistMetaLogImmediatelyHandler
{

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * PersistMetaLogImmediatelyHandler constructor
     *
     * @param GraphAPIAdapter $graphAPIAdapter
     */
    public function __construct(
        GraphAPIAdapter $graphAPIAdapter,
    ) {
        $this->graphApiAdapter = $graphAPIAdapter;
    }

    /**
     * Consumer handler to persist logs from message queue to Meta
     *
     * @param string $message
     */
    public function persistMetaLogImmediately(string $message)
    {
        $logData = json_decode($message, true);
        $this->graphApiAdapter->persistLogToMeta($logData['message'], $logData['context']);
    }
}
