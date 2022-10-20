<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Feed;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\HttpClient;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\HTTP\Client\Curl;

class CategoryCollection
{
    protected $catalogId;

    /**
     * @var CollectionFactory
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $_categoryCollection;

    /**
     * Constructor
     * @param CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection
     * @param FBEHelper $helper
     * @param Curl $curl
     * @param HttpClient $httpClient
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
        FBEHelper $helper,
        Curl $curl,
        HttpClient $httpClient
    ) {
        $this->_storeManager = $storeManager;
        $this->_categoryCollection = $categoryCollection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->fbeHelper = $helper;
        $this->curl = $curl;
        $this->categoryMap = $this->fbeHelper->generateCategoryNameMap();
        $this->httpClient = $httpClient;
    }

    /**
     * @param Category $category
     * get called after user save category, if it is new leaf category, we will create new collection on fb side,
     * if it is changing existed category, we just update the corresponding fb collection.
     * @return null
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
    }

    /**
     * TODO move it to helper or common class
     * @return string|null
     */
    public function getCatalogID()
    {
        if ($this->catalogId == null) {
            $this->catalogId = $this->fbeHelper->getConfigValue('fbe/catalog/id');
        }
        return $this->catalogId;
    }

    /**
     * @param Category $category
     * this method try to get fb product set id from Magento DB, return null if not exist
     * @return string|null
     */
    public function getFBProductSetID(Category $category)
    {
        $key = $this->getCategoryKey($category);
        return $this->fbeHelper->getConfigValue($key);
    }

    /**
     * @param Category $category
     * compose the key for a given category
     * @return string
     */
    public function getCategoryKey(Category $category)
    {
        return 'permanent/fbe/catalog/category/'.$category->getPath();
    }

    /**
     * @param Category $category
     * if the category is Tops we might create "Default Category > Men > Tops"
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
     * @param Category $category
     * @param string $setID
     * save key with a fb product set id
     */
    public function saveFBProductSetID(Category $category, string $setID)
    {
        $key = $this->getCategoryKey($category);
        $this->fbeHelper->saveConfig($key, $setID);
    }

    /**
     * @param Category $category
     * when getLevel() == 1 then it is root category
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
     * @param Category $category
     * get the leave node in category tree, recursion is being used.
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
     * @param Category $category
     * get all children node in category tree, recursion is being used.
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
     * @return Category
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllActiveCategories()
    {
        $categories = $this->_categoryCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1)
            ->setStore($this->_storeManager->getStore());
        return $categories;
    }

    /**
     * initial collection call after fbe installation, please not we only push leaf category to collection,
     * this means if a category contains any category, we won't create a collection for it.
     * @return string|null
     */
    public function pushAllCategoriesToFbCollections()
    {
        $resArray = [];
        $access_token = $this->fbeHelper->getAccessToken();
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
     * @param Category $category
     * call the api creating new product set
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * @return string|null
     */
    public function pushNewCategoryToFB(Category $category)
    {
        $this->fbeHelper->log("pushing category to fb collections: ".$category->getName());
        $access_token = $this->fbeHelper->getAccessToken();
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
     * @param Category $category
     * create filter params for product set api
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
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
     * compose api creating new category (product set) e.g.
     * https://graph.facebook.com/v7.0/$catalogId/product_sets
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
            $this->fbeHelper->getAPIVersion() .
            $category_path;
        $this->fbeHelper->log("Category Create API - " . $category_create_api);
        return $category_create_api;
    }

    /**
     * @param string $set_id
     * compose api creating new category (product set) e.g.
     * https://graph.facebook.com/v7.0/$catalogId/product_sets
     * @return string
     */
    public function getCategoryUpdateApi(string $set_id)
    {
        $set_path = "/" . $set_id ;
        $set_update_api = $this->fbeHelper::FB_GRAPH_BASE_URL .
            $this->fbeHelper->getAPIVersion() .
            $set_path;
        $this->fbeHelper->log("product set update API - " . $set_update_api);
        return $set_update_api;
    }

    /**
     * @param Category $category
     * @param string $set_id
     * call the api update existing product set
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * @return string|null
     */
    public function updateCategoryWithFB(Category $category, string $set_id)
    {
        $access_token = $this->fbeHelper->getAccessToken();
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
     * delete all existing product set on fb side
     * @return null
     */
    public function deleteAllCategoryFromFB()
    {
        $categories = $this->getAllActiveCategories();
        foreach ($categories as $category) {
            $this->deleteCategoryFromFB($category);
        }
    }

    /**
     * @param Category $category
     * call the api delete existing product set under this category
     * When user deletes a category on magento, we first get all sub categories(including itself), and check if we
     * have created a collection set on fb side, if yes then we make delete api call.
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * @return null
     */
    public function deleteCategoryAndSubCategoryFromFB(Category $category)
    {
        $children_categories = $this->getAllChildrenCategories($category);
        foreach ($children_categories as $children_category) {
            $this->deleteCategoryFromFB($children_category);
        }
    }

    /**
     * @param Category $category
     * call the api delete existing product set
     * this should be a low level function call, simple
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * @return null
     */
    public function deleteCategoryFromFB(Category $category)
    {
        $access_token = $this->fbeHelper->getAccessToken();
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
            $this->fbeHelper->getAPIVersion() .
            $set_path;
        // $this->fbeHelper->log("product set deletion API - " . $url);
        $response_body = null;
        try {
            $response_body = $this->httpClient->makeDeleteHttpCall($url);
            if (strpos($response_body, 'true') !== false) {
                $configKey = $this->getCategoryKey($category);
            } else {
                $this->fbeHelper->log("product set deletion failed!!! ");
            }
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
        }
    }
}
