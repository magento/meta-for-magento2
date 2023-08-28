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
     * @param ObjectManagerInterface $objectManager
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param SystemConfig $systemConfig
     * @param ProductMetadataInterface $productMetadata
     * @param GraphAPIConfig $graphAPIConfig
     * @param GraphAPIAdapter $graphAPIAdapter
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
     * @param bool $withMagentoVersion
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
     * @param string $partialURL
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
     * @param string $fullClassName
     * @param array $arguments
     * @return mixed
     */
    public function createObject($fullClassName, array $arguments = [])
    {
        return $this->objectManager->create($fullClassName, $arguments);
    }

    /**
     * Get object
     *
     * @param string $fullClassName
     * @return mixed
     */
    public function getObject($fullClassName)
    {
        return $this->objectManager->get($fullClassName);
    }

    /**
     * Is valid fbid
     *
     * @param string $id
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
     * @param int $storeId
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
     * @param string $info
     * @param mixed[] $context
     */
    public function log($info, array $context = [])
    {
        if (!isset($context['log_type']) || !$this->systemConfig->isMetaTelemetryLoggingEnabled()) {
            $this->logger->info($info);
            return;
        }

        if (isset($context['store_id'])) {
            $context['commerce_merchant_settings_id'] = $this->systemConfig->getCommerceAccountId($context['store_id']);
        }

        $timestamp = ['timestamp' => time()];
        if (isset($context['extra_data'])) {
            $context['extra_data'] = array_merge($context['extra_data'], $timestamp);
        } else {
            $context['extra_data'] = $timestamp;
        }
        $this->logger->info($info, $context);
    }

    /**
     * Log critical
     *
     * @param string $message
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
     * @param array $context
     */
    public function logException(Throwable $e, array $context = [])
    {
        $errorMessage = $e->getMessage();
        $exceptionTrace = $e->getTraceAsString();

        // If the log type is not set or Meta extension logging is not enabled just log the error message and trace.
        if (!isset($context['log_type']) || !$this->systemConfig->isMetaExceptionLoggingEnabled()) {
            $this->logger->error($errorMessage);
            $this->logger->error($exceptionTrace);
            return;
        }

        $context['exception_message'] = $errorMessage;
        $context['exception_code'] = $e->getCode();
        $context['exception_trace'] = $exceptionTrace;

        if (isset($context['store_id'])) {
            $context['commerce_merchant_settings_id'] = $this->systemConfig->getCommerceAccountId($context['store_id']);
        }

        $context['seller_platform_app_version'] = $this->getMagentoVersion();

        // Add extension version to the extra data.
        $extensionVersion = ['extension_version' => $this->systemConfig->getModuleVersion()];
        if (isset($context['extra_data'])) {
            $context['extra_data'] = array_merge($context['extra_data'], $extensionVersion);
        } else {
            $context['extra_data'] = $extensionVersion;
        }

        $this->logger->error($errorMessage, $context);
    }

    /**
     * Log exception and persist immediately with Meta
     *
     * @param Throwable $e
     * @param array $context
     */
    public function logExceptionImmediatelyToMeta(Throwable $e, array $context = []) {
        $context['log_type'] = self::PERSIST_META_LOG_IMMEDIATELY;
        $this->logException($e, $context);
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
     * @param string $pixelId
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
     * @param object $settings
     * @param int $storeId
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
     * @param string $pixelId
     * @param int $storeId
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
     * @param string $pixelId
     * @param int $storeId
     * @return void
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
     * Get fbe access token url endpoint
     *
     * @return string
     */
    public function getFbeAccessTokenUrl()
    {
        $apiVersion = $this->graphAPIAdapter->getGraphApiVersion();
        if (!$apiVersion) {
            return null;
        }
        $baseUrl = $this->getGraphBaseURL();
        return "{$baseUrl}/{$apiVersion}/business_manager_id/access_token";
    }
}
