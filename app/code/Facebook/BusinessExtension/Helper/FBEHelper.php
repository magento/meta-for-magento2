<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use Facebook\BusinessExtension\Helper\Product\Identifier as ProductIdentifier;
use Facebook\BusinessExtension\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

use FacebookAds\Object\ServerSide\AdsPixelSettings;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class FBEHelper extends AbstractHelper
{
    const MAIN_WEBSITE_STORE = 'Main Website Store';
    const MAIN_STORE = 'Main Store';
    const MAIN_WEBSITE = 'Main Website';

    const FB_GRAPH_BASE_URL = "https://graph.facebook.com/";

    const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Meta Business Extension.
    The pixel installed on your website is now deleted.";

    const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
      Please try again.";

    const CURRENT_API_VERSION = "v15.0";

    const MODULE_NAME = "Facebook_BusinessExtension";

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ProductIdentifier
     */
    protected $productIdentifier;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * FBEHelper constructor
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param Logger $logger
     * @param DirectoryList $directorylist
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param ResourceConnection $resourceConnection
     * @param ModuleListInterface $moduleList
     * @param ProductIdentifier $productIdentifier
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Logger $logger,
        DirectoryList $directorylist,
        StoreManagerInterface $storeManager,
        Curl $curl,
        ResourceConnection $resourceConnection,
        ModuleListInterface $moduleList,
        ProductIdentifier $productIdentifier,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->directoryList = $directorylist;
        $this->curl = $curl;
        $this->resourceConnection = $resourceConnection;
        $this->moduleList = $moduleList;
        $this->productIdentifier = $productIdentifier;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @return mixed
     */
    public function getMagentoVersion()
    {
        return $this->objectManager->get(ProductMetadataInterface::class)->getVersion();
    }

    /**
     * @return mixed
     */
    public function getPluginVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return 'magento2';
    }

    /**
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
     * @param $partialURL
     * @return mixed
     */
    public function getUrl($partialURL)
    {
        $urlInterface = $this->getObject(\Magento\Backend\Model\UrlInterface::class);
        return $urlInterface->getUrl($partialURL);
    }

    /**
     * @return mixed
     */
    public function getBaseUrlMedia()
    {
        return $this->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_MEDIA,
            $this->maybeUseHTTPS()
        );
    }

    /**
     * @return bool
     */
    private function maybeUseHTTPS()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    }

    /**
     * @param $fullClassName
     * @param array $arguments
     * @return mixed
     */
    public function createObject($fullClassName, array $arguments = [])
    {
        return $this->objectManager->create($fullClassName, $arguments);
    }

    /**
     * @param $fullClassName
     * @return mixed
     */
    public function getObject($fullClassName)
    {
        return $this->objectManager->get($fullClassName);
    }

    /**
     * @param $id
     * @return bool
     */
    public static function isValidFBID($id)
    {
        return preg_match("/^\d{1,20}$/", $id) === 1;
    }

    /**
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        // Use this function to get a base url respect to host protocol
        return $this->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_WEB,
            $this->maybeUseHTTPS()
        );
    }

    /**
     * @return mixed|string|null
     */
    public function getFBEExternalBusinessId()
    {
        $storedExternalId = $this->systemConfig->getExternalBusinessId();
        if ($storedExternalId) {
            return $storedExternalId;
        }
        $storeId = $this->getStore()->getId();
        $this->log("Store id---" . $storeId);
        return uniqid('fbe_magento_' . $storeId . '_');
    }

    /**
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
        return parse_url(self::getBaseUrl(), PHP_URL_HOST);
    }

    /**
     * @param $info
     */
    public function log($info)
    {
        $this->logger->info($info);
    }

    /**
     * @param $message
     */
    public function logCritical($message)
    {
        $this->logger->critical($message);
    }

    /**
     * @param \Exception $e
     */
    public function logException(\Exception $e)
    {
        $this->logger->error($e->getMessage());
        $this->logger->error($e->getTraceAsString());
        $this->logger->error($e);
    }

    /**
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
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION, $apiVersion);
            $date = new \DateTime();
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE,
                $date->format('Y-m-d H:i:s'));

        } catch (\Exception $e) {
            $this->log("Failed to fetch latest api version with error " . $e->getMessage());
        }

        return $apiVersion ? $apiVersion : self::CURRENT_API_VERSION;
    }

    /*
     * TODO decide which ids we want to return for commerce feature
     * This function queries FBE assets and other commerce related assets. We have stored most of them during FBE setup,
     * such as BM, Pixel, catalog, profiles, ad_account_id. We might want to store or query ig_profiles,
     * commerce_merchant_settings_id, pages in the future.
     * API dev doc https://developers.facebook.com/docs/marketing-api/fbe/fbe2/guides/get-features
     * Here is one example response, we would expect commerce_merchant_settings_id as well in commerce flow
     * {"data":[{"business_manager_id":"12345","onsite_eligible":false,"pixel_id":"12333","profiles":["112","111"],
     * "ad_account_id":"111","catalog_id":"111","pages":["111"],"instagram_profiles":["111"]}]}
     *  usage: $_bm = $_assets['business_manager_ids'];
     */
    public function queryFBEInstalls($external_business_id = null)
    {
        if ($external_business_id == null) {
            $external_business_id = $this->getFBEExternalBusinessId();
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
            $decodeResponse = json_decode($response, true);
            $assets = $decodeResponse['data'][0];
        } catch (\Exception $e) {
            $this->log("Failed to query FBEInstalls" . $e->getMessage());
        }
    }

    /**
     * @param $pixelId
     * @param $pixelEvent
     */
    public function logPixelEvent($pixelId, $pixelEvent)
    {
        $this->log($pixelEvent . " event fired for Pixel id : " . $pixelId);
    }

    /**
     * @return array
     */
    public function deleteConfigKeys()
    {
        $response = [];
        $response['success'] = false;
        try {
            $connection = $this->resourceConnection->getConnection();
            $facebook_config = $this->resourceConnection->getTableName('facebook_business_extension_config');
            $sql = "DELETE FROM $facebook_config WHERE config_key NOT LIKE 'permanent%' ";
            $connection->query($sql);

            $this->systemConfig->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID)
                ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID)
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
     * @param $versionLastUpdate
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
     * @return mixed
     */
    public function getStoreCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param $pixelId
     * @return mixed
     */
    private function fetchAAMSettings($pixelId)
    {
        return AdsPixelSettings::buildFromPixelId($pixelId);
    }

    /**
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
     * @param $settings
     * @return false|string
     */
    private function saveAAMSettings($settings)
    {
        $settingsAsArray = [
            'enableAutomaticMatching' => $settings->getEnableAutomaticMatching(),
            'enabledAutomaticMatchingFields' => $settings->getEnabledAutomaticMatchingFields(),
            'pixelId' => $settings->getPixelId(),
        ];
        $settingsAsString = json_encode($settingsAsArray);
        $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS, $settingsAsString);
        return $settingsAsString;
    }

    /**
     * @param $pixelId
     * @return false|string|null
     */
    public function fetchAndSaveAAMSettings($pixelId)
    {
        $settings = $this->fetchAAMSettings($pixelId);
        if ($settings) {
            return $this->saveAAMSettings($settings);
        }
        return null;
    }

    /**
     * Generates a map of the form : 4 => "Root > Mens > Shoes"
     *
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
     * @param Product $product
     * @return bool|int|string
     */
    public function getRetailerId(Product $product)
    {
        return $this->productIdentifier->getMagentoProductRetailerId($product);
    }

    /**
     * @param $email
     * @param $storeId
     * @return $this
     */
    public function subscribeToNewsletter($email, $storeId)
    {
        $subscriptionClass = '\Magento\Newsletter\Model\SubscriptionManager';
        if (class_exists($subscriptionClass) && method_exists($subscriptionClass, 'subscribe')) {
            /** @var \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager */
            $subscriptionManager = $this->createObject(\Magento\Newsletter\Model\SubscriptionManager::class);
            $subscriptionManager->subscribe($email, $storeId);
        } else {
            // for older Magento versions (2.3 and below)
            /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
            $subscriber = $this->createObject(\Magento\Newsletter\Model\Subscriber::class);
            $subscriber->subscribe($email);
        }
        return $this;
    }
}
