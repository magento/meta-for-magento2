<?php
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

use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManager;
use Meta\BusinessExtension\Logger\Logger;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use FacebookAds\Object\ServerSide\AdsPixelSettings;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FBEHelper extends AbstractHelper
{
    private const MAIN_WEBSITE_STORE = 'Main Website Store';
    private const MAIN_STORE = 'Main Store';
    private const MAIN_WEBSITE = 'Main Website';

    public const FB_GRAPH_BASE_URL = "https://graph.facebook.com/";

    private const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Meta Business Extension.
    The pixel installed on your website is now deleted.";

    private const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
      Please try again.";

    private const CURRENT_API_VERSION = "v15.0";

    private const MODULE_NAME = "Meta_BusinessExtension";

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
     * @var Curl
     */
    private $curl;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * FBEHelper constructor
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param ResourceConnection $resourceConnection
     * @param ModuleListInterface $moduleList
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Curl $curl,
        ResourceConnection $resourceConnection,
        ModuleListInterface $moduleList,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->resourceConnection = $resourceConnection;
        $this->moduleList = $moduleList;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Get magento version
     *
     * @return mixed
     */
    public function getMagentoVersion()
    {
        return $this->objectManager->get(ProductMetadataInterface::class)->getVersion();
    }

    /**
     * Get plugin version
     *
     * @return mixed
     */
    public function getPluginVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return 'magento2';
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
     * Get base url media
     *
     * @return mixed
     */
    public function getBaseUrlMedia()
    {
        return $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
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
        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * Get base url
     *
     * @return mixed
     */
    public function getBaseUrl()
    {
        // Use this function to get a base url respect to host protocol
        return $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
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
     * Get store name
     *
     * @return array|false|int|string|null
     */
    public function getStoreName()
    {
        $frontendName = $this->getStore()->getFrontendName();
        if ($frontendName !== 'Default') {
            return $frontendName;
        }
        $defaultStoreName = $this->getStore()->getGroup()->getName();
        $escapeStrings = ['\r', '\n', '&nbsp;', '\t'];
        $defaultStoreName =
            trim(str_replace($escapeStrings, ' ', $defaultStoreName));
        if (!$defaultStoreName) {
            $defaultStoreName = $this->getStore()->getName();
            $defaultStoreName =
                trim(str_replace($escapeStrings, ' ', $defaultStoreName));
        }
        if ($defaultStoreName && $defaultStoreName !== self::MAIN_WEBSITE_STORE
            && $defaultStoreName !== self::MAIN_STORE
            && $defaultStoreName !== self::MAIN_WEBSITE) {
            return $defaultStoreName;
        }
        return parse_url($this->getBaseUrl(), PHP_URL_HOST); // phpcs:ignore
    }

    /**
     * Log
     *
     * @param string $info
     */
    public function log($info)
    {
        $this->logger->info($info);
    }

    /**
     * Log critical
     *
     * @param string $message
     */
    public function logCritical($message)
    {
        $this->logger->critical($message);
    }

    /**
     * Log exception
     *
     * @param \Exception $e
     */
    public function logException(\Exception $e)
    {
        $this->logger->error($e->getMessage());
        $this->logger->error($e->getTraceAsString());
        $this->logger->error($e);
    }

    /**
     * Get api version
     *
     * @return string|void|null
     */
    public function getAPIVersion()
    {
        $accessToken = $this->systemConfig->getAccessToken();
        if (!$accessToken) {
            $this->log("can't find access token, won't get api update version ");
            return;
        }
        $apiVersion = null;
        try {
            $apiVersion = $this->systemConfig->getApiVersion();
            //$this->log("Current api version : ".$apiVersion);
            $versionLastUpdate = $this->systemConfig->getApiVersionLastUpdate();
            //$this->log("Version last update: ".$versionLastUpdate);
            $isUpdatedVersion = $this->isUpdatedVersion($versionLastUpdate);
            if ($apiVersion && $isUpdatedVersion) {
                //$this->log("Returning the version already stored in db : ".$apiVersion);
                return $apiVersion;
            }
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->get(self::FB_GRAPH_BASE_URL . 'api_version');
            //$this->log("The API call: ".self::FB_GRAPH_BASE_URL.'api_version');
            $response = $this->curl->getBody();
            //$this->log("The API reponse : ".json_encode($response));
            $decodeResponse = json_decode($response);
            $apiVersion = $decodeResponse->api_version;
            //$this->log("The version fetched via API call: ".$apiVersion);
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION,
                $apiVersion
            );
            $date = new \DateTime();
            $this->systemConfig->saveConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE,
                $date->format('Y-m-d H:i:s')
            );

        } catch (\Exception $e) {
            $this->log("Failed to fetch latest api version with error " . $e->getMessage());
        }

        return $apiVersion ? $apiVersion : self::CURRENT_API_VERSION;
    }

    /**
     * Query fbe installs
     *
     * * TODO decide which ids we want to return for commerce feature
     * This function queries FBE assets and other commerce related assets. We have stored most of them during FBE setup,
     * such as BM, Pixel, catalog, profiles, ad_account_id. We might want to store or query ig_profiles,
     * commerce_merchant_settings_id, pages in the future.
     * API dev doc https://developers.facebook.com/docs/marketing-api/fbe/fbe2/guides/get-features
     * Here is one example response, we would expect commerce_merchant_settings_id as well in commerce flow
     * {"data":[{"business_manager_id":"12345","onsite_eligible":false,"pixel_id":"12333","profiles":["112","111"],
     * "ad_account_id":"111","catalog_id":"111","pages":["111"],"instagram_profiles":["111"]}]}
     *  usage: $_bm = $_assets['business_manager_ids'];
     *
     * @param string $external_business_id
     * @return void
     */
    public function queryFBEInstalls($storeId, $external_business_id = null)
    {
        if ($external_business_id == null) {
            $external_business_id = $this->getFBEExternalBusinessId($storeId);
        }
        $accessToken = $this->systemConfig->getAccessToken();
        $urlSuffix = "/fbe_business/fbe_installs?fbe_external_business_id=" . $external_business_id;
        $url = $this::FB_GRAPH_BASE_URL . $this->getAPIVersion() . $urlSuffix;
        $this->log($url);
        try {
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->get($url);
            $response = $this->curl->getBody();
            $this->log("The FBE Install reponse : " . json_encode($response));
        } catch (\Exception $e) {
            $this->log("Failed to query FBEInstalls" . $e->getMessage());
        }
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
     * Delete config keys
     *
     * @return array
     */
    public function deleteConfigKeys()
    {
        $response = [];
        $response['success'] = false;
        try {
            // TODO: Remove this block as part of Data patch refactor. Table no longer used
            $connection = $this->resourceConnection->getConnection();
            $facebook_config = $this->resourceConnection->getTableName('facebook_business_extension_config');
            $sql = "DELETE FROM $facebook_config WHERE config_key NOT LIKE 'permanent%' "; // phpcs:ignore
            $connection->query($sql);

            $this->systemConfig->deleteConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID
            )->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE);

            $response['success'] = true;
            $response['message'] = self::DELETE_SUCCESS_MESSAGE;
        } catch (\Exception $e) {
            $this->log($e->getMessage());
            $response['error_message'] = self::DELETE_FAILURE_MESSAGE;
        }
        return $response;
    }

    /**
     * Is updated version
     *
     * @param string $versionLastUpdate
     * @return bool|null
     */
    public function isUpdatedVersion($versionLastUpdate)
    {
        if (!$versionLastUpdate) {
            return null;
        }
        $monthsSinceLastUpdate = 3;
        try {
            $datetime1 = new \DateTime($versionLastUpdate);
            $datetime2 = new \DateTime();
            $interval = date_diff($datetime1, $datetime2);
            $interval_vars = get_object_vars($interval);
            $monthsSinceLastUpdate = $interval_vars['m'];
            $this->log("Months since last update : " . $monthsSinceLastUpdate);
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
        // Since the previous version is valid for 3 months,
        // I will check to see for the gap to be only 2 months to be safe.
        return $monthsSinceLastUpdate <= 2;
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
     * Generates a map of the form : 4 => "Root > Mens > Shoes"
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @return array
     */
    public function generateCategoryNameMap()
    {
        $categories = $this->getObject(CategoryCollection::class)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('path')
            ->addAttributeToSelect('is_active')
            ->addAttributeToFilter('is_active', 1);
        $name = [];
        $breadcrumb = [];
        foreach ($categories as $category) {
            $entityId = $category->getId();
            $name[$entityId] = $category->getName();
            $breadcrumb[$entityId] = $category->getPath();
        }
        // Converts the product category paths to human readable form.
        // e.g.  "1/2/3" => "Root > Mens > Shoes"
        foreach ($name as $id => $value) {
            $breadcrumb[$id] = implode(" > ", array_filter(array_map(
                function ($innerId) use (&$name) {
                    return isset($name[$innerId]) ? $name[$innerId] : null;
                },
                explode("/", $breadcrumb[$id])
            )));
        }
        return $breadcrumb;
    }

    /**
     * Subscribe to newsletter
     *
     * @param string $email
     * @param int $storeId
     * @return $this
     */
    public function subscribeToNewsletter($email, $storeId)
    {
        $subscriptionClass = '\Magento\Newsletter\Model\SubscriptionManager'; // phpcs:ignore
        if (class_exists($subscriptionClass) && method_exists($subscriptionClass, 'subscribe')) {
            /** @var SubscriptionManager $subscriptionManager */
            $subscriptionManager = $this->createObject(SubscriptionManager::class);
            $subscriptionManager->subscribe($email, $storeId);
        } else {
            // for older Magento versions (2.3 and below)
            /** @var Subscriber $subscriber */
            $subscriber = $this->createObject(Subscriber::class);
            $subscriber->subscribe($email);
        }
        return $this;
    }
}
