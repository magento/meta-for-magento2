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

namespace Meta\BusinessExtension\Logger;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Logger\Handler\Base;
use Meta\BusinessExtension\Helper\FBEHelper;
use Monolog\Logger;

class Handler extends Base
{
  /**
   * Publisher to enable putting logs onto message queue to be persisted async
   *
   * @var PublisherInterface
   */
    private $publisher;

  /**
   * Logging level
   *
   * @var int
   */
    protected $loggerType = Logger::INFO;

  /**
   * File to log to
   *
   * @var string
   */
    protected $fileName = '/var/log/meta/meta-business-extension.log';

  /**
   * Sets the publisher that the handler will use to add logs to message queue
   *
   * @param PublisherInterface $publisher
   */
    public function setPublisher(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

  /**
   * Overriding the write function to put logs onto message queue to be persisted to Meta async and logging locally
   *
   * @param array $record
   */
    protected function write(array $record): void
    {
        $logTypeIsSet = isset($record['context']['log_type']);
        if ($logTypeIsSet && $record['context']['log_type'] === FBEHelper::PERSIST_META_LOG_IMMEDIATELY) {
            $this->publisher->publish('persist.meta.log.immediately', json_encode($record['context']));
        } elseif ($logTypeIsSet && $record['context']['log_type'] === FBEHelper::PERSIST_META_TELEMETRY_LOGS) {
            $this->publisher->publish('persist.meta.telemetry.logs', json_encode($record['context']));
        }
        parent::write($record);
    }
}
