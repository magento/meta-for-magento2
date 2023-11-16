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

class LoggingTemplatePlugin extends LoggingPluginBase
{
    /**
     * Logs exceptions that happen within Meta-owned Templates/Blocks.
     *
     * @param ActionInterface $subject
     * @param callable $progress
     * @param array $args
     * @return ResponseInterface
     * @throws Throwable
     */
    public function aroundFetchView($subject, callable $progress, ...$args)
    {
        return $this->wrapCallableWithErrorLogging(
            'Template fetchView', // log_prefix
            $subject,
            $progress,
            ...$args,
        );
    }
}
