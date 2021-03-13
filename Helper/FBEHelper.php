<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use Facebook\BusinessExtension\Logger\Logger;
use Facebook\BusinessExtension\Model\ConfigFactory;
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

use FacebookAds\Object\ServerSide\AdsPixelSettings;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class FBEHelper extends AbstractHelper
{
    const MAIN_WEBSITE_STORE = 'Main Website Store';
    const MAIN_STORE = 'Main Store';
    const MAIN_WEBSITE = 'Main Website';

    const FB_GRAPH_BASE_URL = "https://graph.facebook.com/";

    const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Facebook Business Extension.
    The pixel installed on your website is now deleted.";

    const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
      Please try again.";

    const CURRENT_API_VERSION = "v9.0";

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
     * @var ConfigFactory
     */
    protected $configFactory;

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
     * FBEHelper constructor
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param ConfigFactory $configFactory
     * @param Logger $logger
     * @param DirectoryList $directorylist
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param ResourceConnection $resourceConnection
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        ConfigFactory $configFactory,
        Logger $logger,
        DirectoryList $directorylist,
        StoreManagerInterface $storeManager,
        Curl $curl,
        ResourceConnection $resourceConnection,
        ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
        $this->directoryList = $directorylist;
        $this->curl = $curl;
        $this->resourceConnection = $resourceConnection;
        $this->moduleList = $moduleList;
    }

    public function getPixelID()
    {
        return $this->getConfigValue('fbpixel/id');
    }

    public function getAccessToken()
    {
        return $this->getConfigValue('fbaccess/token');
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
     * @return string
     */
    public function getPartnerAgent()
    {
        return sprintf(
            '%s-%s-%s',
            $this->getSource(),
            $this->getMagentoVersion(),
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
     * @param $configKey
     * @param $configValue
     */
    public function saveConfig($configKey, $configValue)
    {
        try {
            $configRow = $this->configFactory->create()->load($configKey);
            if ($configRow->getData('config_key')) {
                $configRow->setData('config_value', $configValue);
                $configRow->setData('update_time', time());
            } else {
                $t = time();
                $configRow->setData('config_key', $configKey);
                $configRow->setData('config_value', $configValue);
                $configRow->setData('creation_time', $t);
                $configRow->setData('update_time', $t);
            }
            $configRow->save();
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * @param $configKey
     */
    public function deleteConfig($configKey)
    {
        try {
            $configRow = $this->configFactory->create()->load($configKey);
            $configRow->delete();
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * @param $configKey
     * @return mixed|null
     */
    public function getConfigValue($configKey)
    {
        try {
            $configRow = $this->configFactory->create()->load($configKey);
        } catch (\Exception $e) {
            $this->logException($e);
            return null;
        }
        return $configRow ? $configRow->getConfigValue() : null;
    }

    /**
     * @param $requestParams
     * @param null $accessToken
     * @return string|null
     */
    public function makeHttpRequest($requestParams, $accessToken = null)
    {
        $response = null;
        if ($accessToken == null) {
            $accessToken = $this->getConfigValue('fbaccess/token');
        }
        try {
            $url = $this->getCatalogBatchAPI($accessToken);
            $params = [
                'access_token' => $accessToken,
                'requests' => json_encode($requestParams),
                'item_type' => 'PRODUCT_ITEM',
            ];
            $this->curl->post($url, $params);
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $this->logException($e);
        }
        return $response;
    }

    /**
     * @return mixed|string|null
     */
    public function getFBEExternalBusinessId()
    {
        $stored_external_id = $this->getConfigValue('fbe/external/id');
        if ($stored_external_id) {
            return $stored_external_id;
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
        $accessToken = $this->getAccessToken();
        if ($accessToken == null) {
            $this->log("can't find access token, won't get api update version ");
            return;
        }
        $api_version = null;
        try {

            $configRow = $this->configFactory->create()->load('fb/api/version');
            $api_version = $configRow ? $configRow->getConfigValue() : null;
            //$this->log("Current api version : ".$api_version);
            $versionLastUpdate = $configRow ? $configRow->getUpdateTime() : null;
            //$this->log("Version last update: ".$versionLastUpdate);
            $is_updated_version = $this->isUpdatedVersion($versionLastUpdate);
            if ($api_version && $is_updated_version) {
                //$this->log("Returning the version already stored in db : ".$api_version);
                return $api_version;
            }
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->get(self::FB_GRAPH_BASE_URL . 'api_version');
            //$this->log("The API call: ".self::FB_GRAPH_BASE_URL.'api_version');
            $response = $this->curl->getBody();
            //$this->log("The API reponse : ".json_encode($response));
            $decodeResponse = json_decode($response);
            $api_version = $decodeResponse->api_version;
            //$this->log("The version fetched via API call: ".$api_version);
            $this->saveConfig('fb/api/version', $api_version);

        } catch (\Exception $e) {
            $this->log("Failed to fetch latest api version with error " . $e->getMessage());
        }

        return $api_version ? $api_version : self::CURRENT_API_VERSION;
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
        $accessToken = $this->getAccessToken();
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
     * @param $accessToken
     * @return string
     */
    public function getCatalogBatchAPI($accessToken)
    {
        $catalogId = $this->getConfigValue('fbe/catalog/id');
        $external_business_id = $this->getFBEExternalBusinessId();
        if ($catalogId != null) {
            $catalog_path = "/" . $catalogId . "/items_batch";
        } else {
            $catalog_path = "/fbe_catalog/batch?fbe_external_business_id=" .
                $external_business_id;
        }
        $catalogBatchApi = self::FB_GRAPH_BASE_URL .
            $this->getAPIVersion($accessToken) .
            $catalog_path;
        $this->log("Catalog Batch API - " . $catalogBatchApi);
        return $catalogBatchApi;
    }

    /**
     * @return mixed
     */
    public function getStoreCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @return string
     */
    public function isFBEInstalled()
    {
        $isFbeInstalled = $this->getConfigValue('fbe/installed');
        if ($isFbeInstalled) {
            return 'true';
        }
        return 'false';
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
        $settingsAsString = $this->getConfigValue('fbpixel/aam_settings');
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
        $this->saveConfig('fbpixel/aam_settings', $settingsAsString);
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
}
