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

namespace Meta\BusinessExtension\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Throwable;

class LoggingActionPlugin extends LoggingPluginBase
{
    /**
     * Logs the impression, before the ActionInterface executes.
     *
     * @param ActionInterface $subject
     * @param callable $progress
     * @param array $args
     * @return ResponseInterface
     * @throws Throwable
     */
    public function aroundExecute($subject, callable $progress, ...$args)
    {
        return $this->wrapCallableWithErrorAndImpressionLogging(
            'Action execute',
            $subject,
            $progress,
            ...$args,
        );
    }
}
