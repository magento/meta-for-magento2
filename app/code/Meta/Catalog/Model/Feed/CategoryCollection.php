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

namespace Meta\Catalog\Model\Feed;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\HttpClient;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;

class CategoryCollection
{
    private const FB_GRAPH_BASE_URL = "https://graph.facebook.com/";
    private const CURRENT_API_VERSION = "v15.0";

    /**
     * @var string|null
     */
    private $catalogId;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var array
     */
    private $categoryMap = [];

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollection;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Constructor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollection
     * @param FBEHelper $helper
     * @param Curl $curl
     * @param HttpClient $httpClient
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollection,
        FBEHelper $helper,
        Curl $curl,
        HttpClient $httpClient,
        SystemConfig $systemConfig
    ) {
        $this->storeManager = $storeManager;
        $this->categoryCollection = $categoryCollection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->fbeHelper = $helper;
        $this->curl = $curl;
        $this->categoryMap = $this->generateCategoryNameMap();
        $this->httpClient = $httpClient;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Makes HTTP request after category save
     *
     * Get called after user save category, if it is new leaf category, we will create new collection on fb side,
     * if it is changing existed category, we just update the corresponding fb collection.
     *
     * @param Category $category
     * @return null|string
     */
    public function makeHttpRequestAfterCategorySave(Category $category)
    {
        $set_id = $this->getFBProductSetID($category);
        $this->fbeHelper->log("setid for it is:". (string)$set_id);
        if ($set_id) {
            $response = $this->updateCategoryWithFB($category, $set_id);
            return $response;
        }
        if (!$category->hasChildren()) {
            $response = $this->pushNewCategoryToFB($category);
            return $response;
        }
        $this->fbeHelper->log("category is neither leaf nor"
                                ." used to be leaf (no existing set id found), won't update with fb");
        return null;
    }

    /**
     * Get catalog ID
     *
     * TODO move it to helper or common class
     *
     * @return string|null
     */
    public function getCatalogID()
    {
        if ($this->catalogId == null) {
            $this->catalogId = $this->systemConfig->getCatalogId();
        }
        return $this->catalogId;
    }

    /**
     * Get FB product set ID
     *
     * This method will try to get FB product set id from Magento DB, return null if not exist
     *
     * @param Category $category
     * @return string|null
     */
    public function getFBProductSetID(Category $category)
    {
        $key = $this->getCategoryKey($category);
        return $this->systemConfig->getConfig($key);
    }

    /**
     * Compose the key for a given category
     *
     * @param Category $category
     * @return string
     */
    public function getCategoryKey(Category $category)
    {
        return 'permanent/fbe/catalog/category/'.$category->getPath();
    }

    /**
     * Get category path name
     *
     * If the category is Tops we might create "Default Category > Men > Tops"
     *
     * @param Category $category
     * @return string
     */
    public function getCategoryPathName(Category $category)
    {
        $id = (string)$category->getId();
        if (array_key_exists($id, $this->categoryMap)) {
            return $this->categoryMap[$id];
        }
        return $category->getName();
    }

    /**
     * Save key with a fb product set ID
     *
     * @param Category $category
     * @param string $setID
     */
    public function saveFBProductSetID(Category $category, string $setID)
    {
        $key = $this->getCategoryKey($category);
        $this->systemConfig->saveConfig($key, $setID);
    }

    /**
     * Get root category
     *
     * When getLevel() == 1 then it is root category
     *
     * @param Category $category
     * @return Category
     */
    public function getRootCategory(Category $category)
    {
        $this->fbeHelper->log(
            "searching root category for ". $category->getName(). ' level:'.$category->getLevel()
        );
        if ($category->getLevel() == 1) {
            return $category;
        }
        $parentCategory = $category->getParentCategory();
        while ($parentCategory->getLevel() && $parentCategory->getLevel()>1) {
            $parentCategory = $parentCategory->getParentCategory();
        }
        $this->fbeHelper->log("root category being returned".$parentCategory->getName());
        return $parentCategory;
    }

    /**
     * Get bottom children categories
     *
     * Get the leave node in category tree, recursion is being used.
     *
     * @param Category $category
     * @return Category[]
     */
    public function getBottomChildrenCategories(Category $category)
    {
        $this->fbeHelper->log(
            "searching bottom category for ". $category->getName(). ' level:'.$category->getLevel()
        );
        if (!$category->hasChildren()) {
            $this->fbeHelper->log("no child category for ". $category->getName());
            return [$category];
        }
        $leaf_categories = [];
        $child_categories = $category->getChildrenCategories();
        foreach ($child_categories as $child_category) {
            $sub_leaf_categories = $this->getBottomChildrenCategories($child_category);
            foreach ($sub_leaf_categories as $category) {
                $leaf_categories[] = $category;
            }
        }
        $this->fbeHelper->log(
            "number of leaf category being returned for ". $category->getName() . ": ".count($leaf_categories)
        );
        return $leaf_categories;
    }

    /**
     * Get all children categories
     *
     * Get all children node in category tree recursion is being used.
     *
     * @param Category $category
     * @return Category[]
     */
    public function getAllChildrenCategories(Category $category)
    {
        $this->fbeHelper->log("searching children category for ". $category->getName());
        $all_children_categories = []; // including not only direct child, but also child's child....
        array_push($all_children_categories, $category);
        $children_categories = $category->getChildrenCategories(); // direct children only
        foreach ($children_categories as $children_category) {
            $sub_children_categories = $this->getAllChildrenCategories($children_category);
            foreach ($sub_children_categories as $category) {
                $all_children_categories[] = $category;
            }
        }
        return $all_children_categories;
    }

    /**
     * Get all active categories
     *
     * @return Category
     * @throws LocalizedException
     */
    public function getAllActiveCategories()
    {
        $categories = $this->categoryCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1)
            ->setStore($this->storeManager->getStore());
        return $categories;
    }

    /**
     * Push all categories to FB collections
     *
     * Initial collection call after fbe installation, please not we only push leaf category to collection,
     * this means if a category contains any category, we won't create a collection for it.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function pushAllCategoriesToFbCollections()
    {
        $resArray = [];
        $access_token = $this->systemConfig->getAccessToken();
        if ($access_token == null) {
            $this->fbeHelper->log("can't find access token, abort pushAllCategoriesToFbCollections");
            return;
        }
        $this->fbeHelper->log("pushing all categories to fb collections");
        $categories = $this->getAllActiveCategories();
        foreach ($categories as $category) {
            $syncEnabled =$category->getData("sync_to_facebook_catalog");
            if ($syncEnabled === "0") {
                $this->fbeHelper->log("user disabled category sync ".$category->getName());
                continue;
            }
            $this->fbeHelper->log("user enabled category sync ".$category->getName());
            $set_id = $this->getFBProductSetID($category);
            $this->fbeHelper->log("setid for it is:". (string)$set_id);
            if ($set_id) {
                $response = $this->updateCategoryWithFB($category, $set_id);
                $resArray[] = $response;
                continue;
            }
            if (!$category->hasChildren()) {
                $response = $this->pushNewCategoryToFB($category);
                $resArray[] = $response;
            }
        }
        return json_encode($resArray);
    }

    /**
     * Call the api creating new product set
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @return string|null
     */
    public function pushNewCategoryToFB(Category $category)
    {
        $this->fbeHelper->log("pushing category to fb collections: ".$category->getName());
        $access_token = $this->systemConfig->getAccessToken();
        if ($access_token == null) {
            $this->fbeHelper->log("can't find access token, won't push new catalog category ");
            return;
        }
        $response = null;
        try {
            $url = $this->getCategoryCreateApi();
            if ($url == null) {
                return;
            }
            $params = [
                'access_token' => $access_token,
                'name' => $this->getCategoryPathName($category),
                'filter' => $this->getCategoryProductFilter($category),
            ];
            $this->curl->post($url, $params);
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
        }
        $this->fbeHelper->log("response from fb: ".$response);
        $response_obj = json_decode($response, true);
        if (array_key_exists('id', $response_obj)) {
            $set_id = $response_obj['id'];
            $this->saveFBProductSetID($category, $set_id);
            $this->fbeHelper->log(sprintf("saving category %s and set_id %s", $category->getName(), $set_id));
        }
        return $response;
    }

    /**
     * Create filter params for product set api
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
     *
     * @param Category $category
     * @return string
     */
    public function getCategoryProductFilter(Category $category)
    {
        $product_collection = $this->productCollectionFactory->create();
        $product_collection->addAttributeToSelect('sku');
        $product_collection->distinct(true);
        $product_collection->addCategoriesFilter(['eq' => $category->getId()]);
        $product_collection->getSelect()->limit(10000);
        $this->fbeHelper->log("collection count:".(string)count($product_collection));

        $ids = [];
        foreach ($product_collection as $product) {
            array_push($ids, "'".$product->getId()."'");
        }
        $filter = sprintf("{'retailer_id': {'is_any': [%s]}}", implode(',', $ids));
//        $this->fbeHelper->log("filter:".$filter);

        return $filter;
    }

    /**
     * Compose api creating new category (product set) e.g.
     *
     * Api link: https://graph.facebook.com/v7.0/$catalogId/product_sets
     *
     * @return string | null
     */
    public function getCategoryCreateApi()
    {
        $catalogId = $this->getCatalogID();
        if ($catalogId == null) {
            $this->fbeHelper->log("cant find catalog id, can't make category create api");
        }
        $category_path = "/" . $catalogId . "/product_sets";

        $category_create_api = $this->fbeHelper::FB_GRAPH_BASE_URL .
            $this->getAPIVersion() .
            $category_path;
        $this->fbeHelper->log("Category Create API - " . $category_create_api);
        return $category_create_api;
    }

    /**
     * Compose api creating new category (product set) e.g.
     *
     * Api link: https://graph.facebook.com/v7.0/$catalogId/product_sets
     *
     * @param string $set_id
     * @return string
     */
    public function getCategoryUpdateApi(string $set_id)
    {
        $set_path = "/" . $set_id ;
        $set_update_api = $this->fbeHelper::FB_GRAPH_BASE_URL .
            $this->getAPIVersion() .
            $set_path;
        $this->fbeHelper->log("product set update API - " . $set_update_api);
        return $set_update_api;
    }

    /**
     * Call the api update existing product set
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @param string $set_id
     * @return string|null
     */
    public function updateCategoryWithFB(Category $category, string $set_id)
    {
        $access_token = $this->systemConfig->getAccessToken();
        if ($access_token == null) {
            $this->fbeHelper->log("can't find access token, won't update category with fb ");
        }
        $response = null;
        try {
            $url = $this->getCategoryUpdateApi($set_id);
            $params = [
                'access_token' => $access_token,
                'name' => $this->getCategoryPathName($category),
                'filter' => $this->getCategoryProductFilter($category),
            ];
            $this->curl->post($url, $params);
            $response = $this->curl->getBody();
            $this->fbeHelper->log("update category api response from fb:". $response);
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
        }
        return $response;
    }

    /**
     * Delete all existing product set on FB side
     *
     * @return void
     * @throws LocalizedException
     */
    public function deleteAllCategoryFromFB()
    {
        $categories = $this->getAllActiveCategories();
        foreach ($categories as $category) {
            $this->deleteCategoryFromFB($category);
        }
    }

    /**
     * Call the api delete existing product set under category
     *
     * When user deletes a category on magento, we first get all sub categories(including itself), and check if we
     * have created a collection set on fb side, if yes then we make delete api call.
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @return void
     */
    public function deleteCategoryAndSubCategoryFromFB(Category $category)
    {
        $children_categories = $this->getAllChildrenCategories($category);
        foreach ($children_categories as $children_category) {
            $this->deleteCategoryFromFB($children_category);
        }
    }

    /**
     * Call the api delete existing product set
     *
     * This should be a low level function call, simple
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     *
     * @param Category $category
     * @return void
     */
    public function deleteCategoryFromFB(Category $category)
    {
        $access_token = $this->systemConfig->getAccessToken();
        if ($access_token == null) {
            $this->fbeHelper->log("can't find access token, won't do category delete");
            return;
        }
        $this->fbeHelper->log("category name:". $category->getName());
        $set_id = $this->getFBProductSetID($category);
        if ($set_id == null) {
            $this->fbeHelper->log("cant find product set id, won't make category delete api");
            return;
        }
        $set_path = "/" . $set_id . "?access_token=". $access_token;
        $url = $this->fbeHelper::FB_GRAPH_BASE_URL .
            $this->getAPIVersion() .
            $set_path;
        try {
            $response_body = $this->httpClient->makeDeleteHttpCall($url);
            if (strpos($response_body, 'true') === false) {
                $this->fbeHelper->log("product set deletion failed!!! ");
            }
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
        }
    }

    /**
     * Generates a map of the form : 4 => "Root > Mens > Shoes"
     *
     * @return array
     */
    public function generateCategoryNameMap()
    {
        $categories = $categories = $this->categoryCollection->create()
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
        foreach (array_keys($name) as $id) {
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
     * Get api version
     *
     * @return string|void|null
     */
    public function getAPIVersion()
    {
        $accessToken = $this->systemConfig->getAccessToken();
        if (!$accessToken) {
            $this->fbeHelper->log("can't find access token, won't get api update version ");
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
            //$this->fbeHelper->log("The API call: ".self::FB_GRAPH_BASE_URL.'api_version');
            $response = $this->curl->getBody();
            //$this->fbeHelper->log("The API reponse : ".json_encode($response));
            $decodeResponse = json_decode($response);
            $apiVersion = $decodeResponse->api_version;
            //$this->fbeHelper->log("The version fetched via API call: ".$apiVersion);
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
            $this->fbeHelper->log("Failed to fetch latest api version with error " . $e->getMessage());
        }

        return $apiVersion ? $apiVersion : self::CURRENT_API_VERSION;
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
            $this->fbeHelper->log("Months since last update : " . $monthsSinceLastUpdate);
        } catch (\Exception $e) {
            $this->fbeHelper->log($e->getMessage());
        }
        // Since the previous version is valid for 3 months,
        // I will check to see for the gap to be only 2 months to be safe.
        return $monthsSinceLastUpdate <= 2;
    }
}
