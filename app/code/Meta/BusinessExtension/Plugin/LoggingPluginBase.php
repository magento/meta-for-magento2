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

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Throwable;

abstract class LoggingPluginBase
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreRepositoryInterface
     */
    public $storeRepository;

    /**
     * Construct LoggingPluginBase.
     *
     * @param FBEHelper                $fbeHelper
     * @param RequestInterface         $request
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        FBEHelper                $fbeHelper,
        RequestInterface         $request,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->request = $request;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Wraps the given $progress function with error and start/end logging.
     *
     * @param  string   $log_prefix
     * @param  mixed    $subject
     * @param  callable $progress
     * @param  array    $args
     * @return mixed
     * @throws Throwable
     */
    public function wrapCallableWithErrorAndImpressionLogging(
        string   $log_prefix,
        $subject,
        callable $progress,
        ...$args
    ) {
        return $this->wrapCallableWithLogging(
            true, // $should_log_errors
            true, // $should_log_impressions,
            $log_prefix,
            $subject,
            $progress,
            ...$args
        );
    }

    /**
     * Wraps the given $progress function with error logging.
     *
     * @param  string   $log_prefix
     * @param  mixed    $subject
     * @param  callable $progress
     * @param  array    $args
     * @return mixed
     * @throws Throwable
     */
    public function wrapCallableWithErrorLogging(
        string   $log_prefix,
        $subject,
        callable $progress,
        ...$args
    ) {
        return $this->wrapCallableWithLogging(
            true, // $should_log_errors
            false, // $should_log_impressions,
            $log_prefix,
            $subject,
            $progress,
            ...$args
        );
    }

    /**
     * Wraps the given $progress function with logging.
     *
     * @param  bool     $should_log_errors
     * @param  bool     $should_log_impressions
     * @param  string   $log_prefix
     * @param  mixed    $subject
     * @param  callable $progress
     * @param  array    $args
     * @return mixed
     * @throws Throwable
     */
    private function wrapCallableWithLogging(
        bool     $should_log_errors,
        bool     $should_log_impressions,
        string   $log_prefix,
        $subject,
        callable $progress,
        ...$args
    ) {
        $classname = $this->getClassnameForLogging($subject);
        if (!preg_match('/^Meta\\\\/', $classname)) {
            return $progress(...$args);
        }

        if ($should_log_impressions) {
            $this->fbeHelper->logTelemetryToMeta(
                $log_prefix . ': ' . $classname,
                $this->getTelemetryLogData($log_prefix, $subject, 'Start'),
            );
        }

        try {
            return $progress(...$args);
        } catch (Throwable $ex) {
            if ($should_log_errors) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $ex,
                    $this->getErrorLogData($subject),
                );
            }

            throw $ex;
        } finally {
            if ($should_log_impressions) {
                $this->fbeHelper->logTelemetryToMeta(
                    $log_prefix . ': ' . $classname,
                    $this->getTelemetryLogData($log_prefix, $subject, 'End'),
                );
            }
        }
    }

    /**
     * Gets the error log data.
     *
     * @param  mixed $subject
     * @return array
     */
    private function getErrorLogData($subject)
    {
        $storeId = $this->getMaybeStoreID();
        return [
            'event' => 'Error: ' . $this->getClassnameForLogging($subject),
            'event_type' => 'admin_ui_error',
            'store_id' => $storeId,
        ];
    }

    /**
     * Gets the telemetry log data to log for a given Step.
     *
     * @param  string $log_prefix
     * @param  mixed  $subject
     * @param  string $step
     * @return array
     */
    private function getTelemetryLogData(string $log_prefix, $subject, string $step)
    {
        $storeId = $this->getMaybeStoreID();
        return [
            'flow_name' => $log_prefix . ': ' . $this->getClassnameForLogging($subject),
            'store_id' => $storeId,
            'flow_step' => $step,
        ];
    }

    /**
     * Gets the data to log for a given Step.
     *
     * @param  mixed $subject
     * @return string
     */
    private function getClassnameForLogging($subject)
    {
        return get_class($subject);
    }

    /**
     * Returns an inferred storeID for the current request.
     *
     * @return string|null
     */
    private function getMaybeStoreID()
    {
        $storeId = $this->request->getParam('store_id')
            ?? $this->request->getParam('storeID')
            ?? $this->request->getParam('storeId')
            ?? $this->fbeHelper->getStore()->getId();
        if ($storeId != null) {
            return $storeId;
        }

        $stores = $this->storeRepository->getList();
        $firstStore = array_shift($stores);
        if ($firstStore != null) {
            return $firstStore['store_id'];
        }
        return null;
    }
}
