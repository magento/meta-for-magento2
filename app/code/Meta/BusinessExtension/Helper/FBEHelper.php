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

namespace Meta\BusinessExtension\Helper;

use Meta\BusinessExtension\Logger\Logger;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ProductMetadata as FrameworkProductMetaData;
use Magento\Framework\ObjectManagerInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use FacebookAds\Object\ServerSide\AdsPixelSettings;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Security\Model\AdminSessionsManager;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FBEHelper
{
    private const URL_TYPE_WEB = 'web';

    public const PERSIST_META_LOG_IMMEDIATELY = 'persist_meta_log_immediately';
    public const PERSIST_META_TELEMETRY_LOGS = 'persist_meta_telemetry_logs';

    /**
     * @var GraphAPIConfig
     */
    private $graphAPIConfig;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * FBEHelper constructor
     *
     * @param ObjectManagerInterface   $objectManager
     * @param Logger                   $logger
     * @param StoreManagerInterface    $storeManager
     * @param SystemConfig             $systemConfig
     * @param ProductMetadataInterface $productMetadata
     * @param GraphAPIConfig           $graphAPIConfig
     * @param GraphAPIAdapter          $graphAPIAdapter
     */
    public function __construct(
        ObjectManagerInterface   $objectManager,
        Logger                   $logger,
        StoreManagerInterface    $storeManager,
        SystemConfig             $systemConfig,
        ProductMetadataInterface $productMetadata,
        GraphAPIConfig           $graphAPIConfig,
        GraphAPIAdapter          $graphAPIAdapter
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->systemConfig = $systemConfig;
        $this->productMetadata = $productMetadata;
        $this->graphAPIConfig = $graphAPIConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
    }

    /**
     * Get Graph API adapter
     *
     * @return GraphAPIAdapter
     */
    public function getGraphAPIAdapter(): GraphAPIAdapter
    {
        return $this->graphAPIAdapter;
    }

    /**
     * Returns the properly configured Graph Base URL
     */
    public function getGraphBaseURL()
    {
        return $this->graphAPIConfig->getGraphBaseURL();
    }

    /**
     * Get magento version
     *
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        return $this->systemConfig->getModuleVersion();
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->productMetadata->getEdition() == FrameworkProductMetaData::EDITION_NAME
            ? 'magento_opensource' : 'adobe_commerce';
    }

    /**
     * Get partner agent
     *
     * @param  bool $withMagentoVersion
     * @return string
     */
    public function getPartnerAgent($withMagentoVersion = false)
    {
        return sprintf(
            '%s-%s-%s',
            $this->getSource(),
            $withMagentoVersion ? $this->getMagentoVersion() : '0.0.0',
            $this->getPluginVersion()
        );
    }

    /**
     * Get url
     *
     * @param  string $partialURL
     * @return mixed
     */
    public function getUrl($partialURL)
    {
        $urlInterface = $this->getObject(\Magento\Backend\Model\UrlInterface::class);
        return $urlInterface->getUrl($partialURL);
    }

    /**
     * Create object
     *
     * @param  string $fullClassName
     * @param  array  $arguments
     * @return mixed
     */
    public function createObject($fullClassName, array $arguments = [])
    {
        return $this->objectManager->create($fullClassName, $arguments);
    }

    /**
     * Get object
     *
     * @param  string $fullClassName
     * @return mixed
     */
    public function getObject($fullClassName)
    {
        return $this->objectManager->get($fullClassName);
    }

    /**
     * Is valid fbid
     *
     * @param  string $id
     * @return bool
     */
    public static function isValidFBID($id) // phpcs:ignore
    {
        return preg_match("/^\d{1,20}$/", $id) === 1;
    }

    /**
     * Get store
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        $current_store = $this->storeManager->getStore();
        return $current_store ?: $this->storeManager->getDefaultStoreView();
    }

    /**
     * Get base url
     *
     * @return mixed
     */
    public function getBaseUrl()
    {
        // Use this function to get a base url respect to host protocol
        return $this->getStore()->getBaseUrl(self::URL_TYPE_WEB);
    }

    /**
     * Get fbe external business id
     *
     * @param  int $storeId
     * @return mixed|string|null
     */
    public function getFBEExternalBusinessId($storeId)
    {
        $storedExternalId = $this->systemConfig->getExternalBusinessId($storeId);
        if ($storedExternalId) {
            return $storedExternalId;
        }
        $storeId = $this->getStore()->getId();
        $this->log("Store id---" . $storeId);
        return uniqid('fbe_magento_' . $storeId . '_');
    }

    /**
     * Log
     *
     * @param string  $info
     * @param mixed[] $context
     */
    public function log($info, array $context = [])
    {
        if (!isset($context['log_type'])) {
            $this->logger->info($info, $context);
            return;
        }

        $extraData = [
            'timestamp' => time(),
            'seller_platform_app_version' => $this->getMagentoVersion(),
            'extension_version' => $this->systemConfig->getModuleVersion()
        ];

        if (isset($context['store_id'])) {
            $extraData['store_id'] = $context['store_id'];
            $context['commerce_merchant_settings_id'] = $this->systemConfig->getCommerceAccountId($context['store_id']);
        }

        if (isset($context['extra_data'])) {
            $context['extra_data'] = array_merge($context['extra_data'], $extraData);
        } else {
            $context['extra_data'] = $extraData;
        }

        $this->logger->info($info, $context);
    }

    /**
     * Log critical
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function logCritical($message, array $context = [])
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log exception
     *
     * @param Throwable $e
     * @param array     $context
     */
    public function logException(Throwable $e, array $context = [])
    {
        $errorMessage = $e->getMessage();
        $exceptionTrace = $e->getTraceAsString();
        $exceptionCode = $e->getCode();

        $this->logExceptionDetails($exceptionCode, $errorMessage, $exceptionTrace, $context);
    }

    /**
     * Log exception details
     *
     * @param int    $code
     * @param string $message
     * @param string $traceAsString
     * @param array  $context
     */
    public function logExceptionDetails($code, $message, $traceAsString, array $context = [])
    {
        // If the log type is not set just log the error message and trace.
        if (!isset($context['log_type'])) {
            $this->logger->error($message);
            $this->logger->error($traceAsString);
            return;
        }

        $context['exception_message'] = $message;
        $context['exception_code'] = $code;
        $context['exception_trace'] = $traceAsString;
        $context['seller_platform_app_version'] = $this->getMagentoVersion();

        $extraData = ['extension_version' => $this->systemConfig->getModuleVersion()];

        if (isset($context['store_id'])) {
            $extraData['store_id'] = $context['store_id'];
            $context['commerce_merchant_settings_id'] = $this->systemConfig->getCommerceAccountId($context['store_id']);
            $context['external_business_id'] = $this->systemConfig->getExternalBusinessId($context['store_id']);
            $context['commerce_partner_integration_id'] =
                $this->systemConfig->getCommercePartnerIntegrationId($context['store_id']);
            $context['page_id'] = $this->systemConfig->getPageId($context['store_id']);
            $context['pixel_id'] = $this->systemConfig->getPixelId($context['store_id']);
        }

        if (isset($context['extra_data'])) {
            $context['extra_data'] = array_merge($context['extra_data'], $extraData);
        } else {
            $context['extra_data'] = $extraData;
        }

        $this->logger->error($message, $context);
    }

    /**
     * Log exception and persist immediately with Meta
     *
     * @param Throwable $e
     * @param array     $context
     */
    public function logExceptionImmediatelyToMeta(Throwable $e, array $context = [])
    {
        $context['log_type'] = self::PERSIST_META_LOG_IMMEDIATELY;
        $this->logException($e, $context);
    }

    /**
     * Log error details and persist immediately with Meta.
     *
     * `logExceptionImmediatelyToMeta` should be preferred whenever a /Throwable is available.
     *
     * @param int    $code
     * @param string $message
     * @param string $traceAsString
     * @param array  $context
     */
    public function logExceptionDetailsImmediatelyToMeta($code, $message, $traceAsString, array $context = [])
    {
        $context['log_type'] = self::PERSIST_META_LOG_IMMEDIATELY;
        $this->logExceptionDetails($code, $message, $traceAsString, $context);
    }

    /**
     * Log telemetry and persist with Meta
     *
     * @param string $message
     * @param array  $context
     */
    public function logTelemetryToMeta(string $message, array $context = [])
    {
        $context['log_type'] = self::PERSIST_META_TELEMETRY_LOGS;
        $this->log($message, $context);
    }

    /**
     * Log pixel event
     *
     * @param string $pixelId
     * @param string $pixelEvent
     */
    public function logPixelEvent($pixelId, $pixelEvent)
    {
        $this->log($pixelEvent . " event fired for Pixel id : " . $pixelId);
    }

    /**
     * Get store currency code
     *
     * @return mixed
     */
    public function getStoreCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Fetch aam settings
     *
     * @param  string $pixelId
     * @return mixed
     */
    private function fetchAAMSettings($pixelId)
    {
        return AdsPixelSettings::buildFromPixelId($pixelId);
    }

    /**
     * Get aam settings
     *
     * @return AdsPixelSettings|null
     */
    public function getAAMSettings()
    {
        $settingsAsString = $this->systemConfig->getPixelAamSettings();
        if ($settingsAsString) {
            $settingsAsArray = json_decode($settingsAsString, true);
            if ($settingsAsArray) {
                $settings = new AdsPixelSettings();
                $settings->setPixelId($settingsAsArray['pixelId']);
                $settings->setEnableAutomaticMatching($settingsAsArray['enableAutomaticMatching']);
                $settings->setEnabledAutomaticMatchingFields($settingsAsArray['enabledAutomaticMatchingFields']);
                return $settings;
            }
        }
        return null;
    }

    /**
     * Save aam settings
     *
     * @param  object $settings
     * @param  int    $storeId
     * @return false|string
     */
    private function saveAAMSettings($settings, $storeId)
    {
        $settingsAsArray = [
            'enableAutomaticMatching' => $settings->getEnableAutomaticMatching(),
            'enabledAutomaticMatchingFields' => $settings->getEnabledAutomaticMatchingFields(),
            'pixelId' => $settings->getPixelId(),
        ];
        $settingsAsString = json_encode($settingsAsArray);
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS,
            $settingsAsString,
            $storeId
        );
        return $settingsAsString;
    }

    /**
     * Fetch and save aam settings
     *
     * @param  string $pixelId
     * @param  int    $storeId
     * @return false|string|null
     */
    public function fetchAndSaveAAMSettings($pixelId, $storeId)
    {
        $settings = $this->fetchAAMSettings($pixelId);
        if ($settings) {
            return $this->saveAAMSettings($settings, $storeId);
        }
        return null;
    }

    /**
     * Check admin permissions
     *
     * @return void
     * @throws LocalizedException
     */
    public function checkAdminEndpointPermission()
    {
        $adminSession = $this->createObject(AdminSessionsManager::class)
            ->getCurrentSession();
        if (!$adminSession || $adminSession->getStatus() != 1) {
            throw new LocalizedException(__('This endpoint is for logged in admin and ajax only.'));
        }
    }

    /**
     * Return unique trace id for telemetry flows
     *
     * @return string
     */
    public function genUniqueTraceID(): string
    {
        return uniqid("magento_");
    }

    /**
     * Return current time in milliseconds
     *
     * @return int
     */
    public function getCurrentTimeInMS(): int
    {
        return (int)(microtime(true) * 1000);
    }
}
