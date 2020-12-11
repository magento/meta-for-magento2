<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Feed;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\HttpClient;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\HTTP\Client\Curl;

class CategoryCollection
{
    protected $catalog_id;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var FBEHelper
     */
    private $_fbeHelper;
    /**
     * @var Curl
     */
    private $_curl;
    /**
     * @var Array
     */
    private $_category_map;
    /**
     * @var HttpClient
     */
    private $_http_client;

    /**
     * Constructor
     * @param CollectionFactory $productCollectionFactory
     * @param FBEHelper $helper
     * @param Curl $curl
     * @param HttpClient $httpClient
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        FBEHelper $helper,
        Curl $curl,
        HttpClient $httpClient
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_fbeHelper = $helper;
        $this->_curl = $curl;
        $this->_category_map = $this->_fbeHelper->generateCategoryNameMap();
        $this->_http_client = $httpClient;
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
        $this->_fbeHelper->log("setid for it is:". (string)$set_id);
        if ($set_id)
        {
            $response = $this->updateCategoryWithFB($category, $set_id);
            return $response;
        }
        if(!$category->hasChildren())
        {
            $response = $this->pushNewCategoryToFB($category);
            return $response;
        }
        $this->_fbeHelper->log("category is neither leaf nor used to be leaf (no existing set id found), won't update with fb");

        return;
    }

    /**
     * TODO move it to helper or common class
     * @return string|null
     */
    public function getCatelogID()
    {
        if($this->catalog_id == null){
            $this->catalog_id = $this->_fbeHelper->getConfigValue('fbe/catalog/id');
        }
        return $this->catalog_id;
    }

    /**
     * @param Category $category
     * this method try to get fb product set id from Magento DB, return null if not exist
     * @return string|null
     */
    public function getFBProductSetID(Category $category){
        $key = $this->getCategoryKey($category);
        return $this->_fbeHelper->getConfigValue($key);
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
        if(array_key_exists($id, $this->_category_map))
        {
            return $this->_category_map[$id];
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
        $this->_fbeHelper->saveConfig($key, $setID);
    }

    /**
     * @param Category $category
     * when getLevel() == 1 then it is root category
     * @return Category
     */
    public function getRootCategory(Category $category)
    {
        $this->_fbeHelper->log("searching root category for ". $category->getName(). ' level:'.$category->getLevel());
        if($category->getLevel() == 1){
            return $category;
        }
        $parentCategory = $category->getParentCategory();
        while ($parentCategory->getLevel() && $parentCategory->getLevel()>1){
            $parentCategory = $parentCategory->getParentCategory();
        }
        $this->_fbeHelper->log("root category being returned". $parentCategory->getName(), ' level:'.$parentCategory->getLevel());
        return $parentCategory;
    }

    /**
     * @param Category $category
     * get the leave node in category tree, recursion is being used.
     * @return Category[]
     */
    public function getBottomChildrenCategories(Category $category)
    {
        $this->_fbeHelper->log("searching bottom category for ". $category->getName(). ' level:'.$category->getLevel());
        if( !$category->hasChildren())
        {
            $this->_fbeHelper->log("no child category for ". $category->getName());
            return array($category);
        }
        $leaf_categories = [];
        $child_categories = $category->getChildrenCategories();
        foreach ($child_categories as $child_category)
        {
            $sub_leaf_categories = $this->getBottomChildrenCategories($child_category);
            $leaf_categories = array_merge($leaf_categories, $sub_leaf_categories);
        }
        $this->_fbeHelper->log("number of leaf category being returned for ". $category->getName(). ": ".count($leaf_categories));
        return $leaf_categories;
    }

    /**
     * @param Category $category
     * get all children node in category tree, recursion is being used.
     * @return Category[]
     */
    public function getAllChildrenCategories(Category $category)
    {
        $this->_fbeHelper->log("searching children category for ". $category->getName());
        $all_children_categories = []; // including not only direct child, but also child's child....
        array_push($all_children_categories, $category);
        $children_categories = $category->getChildrenCategories(); // direct children only
        foreach ($children_categories as $children_category)
        {
            $sub_children_categories = $this->getAllChildrenCategories($children_category);
            $all_children_categories = array_merge($all_children_categories, $sub_children_categories);
        }
        return $all_children_categories;
    }

    /**
     * @return Category
     */
    public function getAllActiveCategories()
    {
        $categories = $this->_fbeHelper
            ->getObject(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1);
        return $categories;
    }

    /**
     * initial collection call after fbe installation, please not we only push leaf category to collection,
     * this means if a category contains any category, we won't create a collection for it.
     * @return string|null
     */
    public function pushAllCategoriesToFbCollections()
    {
        $access_token = $this->_fbeHelper->getAccessToken();
        if($access_token == null)
        {
            $this->_fbeHelper->log("can't find access token, abort pushAllCategoriesToFbCollections");
            return;
        }
        $this->_fbeHelper->log("pushing all categories to fb collections");
        $categories = $this->getAllActiveCategories();
        foreach ($categories as $category)
        {
            $set_id = $this->getFBProductSetID($category);
            $this->_fbeHelper->log("setid for it is:". (string)$set_id);
            if ($set_id)
            {
                $response = $this->updateCategoryWithFB($category, $set_id);
                continue;
            }
            if(!$category->hasChildren())
            {
                $this->pushNewCategoryToFB($category);
            }
        }
    }

    /**
     * @param Category $category
     * call the api creating new product set
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * @return string|null
     */
    public function pushNewCategoryToFB(Category $category) {
        $this->_fbeHelper->log("pushing category to fb collections: ".$category->getName());
        $access_token = $this->_fbeHelper->getAccessToken();
        if ($access_token == null)
        {
            $this->_fbeHelper->log("can't find access token, won't push new catalog category ");
            return;
        }
        $response = null;
        try {
            $url = $this->getCategoryCreateApi();
            if( $url == null){
                return;
            }
            $params = array(
                'access_token' => $access_token,
                'name' => $this->getCategoryPathName($category),
                'filter' => $this->getCategoryProductFilter($category),
            );
            $this->_curl->post($url, $params);
            $response = $this->_curl->getBody();
        } catch (\Exception $e) {
            $this->logException($e);
        }
        $this->_fbeHelper->log("response from fb: ".$response);
        $response_obj = json_decode($response, true);
        if(array_key_exists('id', $response_obj)){
            $set_id = $response_obj['id'];
            $this->saveFBProductSetID($category, $set_id);
            $this->_fbeHelper->log(sprintf("saving category %s and set_id %s", $category->getName(), $set_id));
        }
        return $response;
    }

    /**
     * @param Category $category
     * create filter params for product set api
     * https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
     * @return \array[]
     */
    public function getCategoryProductFilter(Category $category)
    {
        $product_collection = $this->productCollectionFactory->create();
        $product_collection->addAttributeToSelect('sku');
        $product_collection->distinct(true);
        $product_collection->addCategoriesFilter(['eq' => $category->getId()]);
        $product_collection->getSelect()->limit(10000);
        $this->_fbeHelper->log("collection count:".(string)count($product_collection));

        $ids = [];
        foreach ($product_collection as $product)
        {
            array_push($ids, "'".$product->getId()."'");
        }
        $filter = sprintf("{'retailer_id': {'is_any': [%s]}}", implode(',', $ids));
//        $this->_fbeHelper->log("filter:".$filter);

        return $filter;
    }

    /**
     * compose api creating new category (product set) e.g.
     * https://graph.facebook.com/v7.0/$CATALOG_ID/product_sets
     * @return string | null
     */
    public function getCategoryCreateApi() {
        $catalog_id = $this->getCatelogID();
        if ($catalog_id == null) {
            $this->_fbeHelper->log("cant find catalog id, can't make category create api");
            return;
        }
        $category_path = "/" . $catalog_id . "/product_sets";

        $category_create_api = $this->_fbeHelper::FB_GRAPH_BASE_URL .
            $this->_fbeHelper->getAPIVersion() .
            $category_path;
        $this->_fbeHelper->log("Category Create API - " . $category_create_api);
        return $category_create_api;
    }

    /**
     * @param string $set_id
     * compose api creating new category (product set) e.g.
     * https://graph.facebook.com/v7.0/$CATALOG_ID/product_sets
     * @return string
     */
    public function getCategoryUpdateApi(string $set_id) {
        $set_path = "/" . $set_id ;
        $set_update_api = $this->_fbeHelper::FB_GRAPH_BASE_URL .
            $this->_fbeHelper->getAPIVersion() .
            $set_path;
        $this->_fbeHelper->log("product set update API - " . $set_update_api);
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
        $access_token = $this->_fbeHelper->getAccessToken();
        if ($access_token == null)
        {
            $this->_fbeHelper->log("can't find access token, won't update category with fb ");
            return;
        }
        $response = null;
        try {
            $url = $this->getCategoryUpdateApi($set_id);
            $params = array(
                'access_token' => $access_token,
                'name' => $this->getCategoryPathName($category),
                'filter' => $this->getCategoryProductFilter($category),
            );
            $this->_curl->post($url, $params);
            $response = $this->_curl->getBody();
            $this->_fbeHelper->log("update category api response from fb:". $response);
        } catch (\Exception $e) {
            $this->_fbeHelper->logException($e);
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
        foreach ($categories as $category)
        {
            $this->deleteCategoryFromFB($category);
        }
        return;
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
        foreach ($children_categories as $children_category)
        {
            $this->deleteCategoryFromFB($children_category);
        }
        return;
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
        $access_token = $this->_fbeHelper->getAccessToken();
        if ($access_token == null)
        {
            $this->_fbeHelper->log("can't find access token, won't do category delete");
            return;
        }
        $this->_fbeHelper->log("category name:". $category->getName());
        $set_id = $this->getFBProductSetID($category);
        if ($set_id == null)
        {
            $this->_fbeHelper->log("cant find product set id, won't make category delete api");
            return;
        }
        $set_path = "/" . $set_id . "?access_token=". $access_token;
        $url = $this->_fbeHelper::FB_GRAPH_BASE_URL .
            $this->_fbeHelper->getAPIVersion() .
            $set_path;
        // $this->_fbeHelper->log("product set deletion API - " . $url);
        $response_body = null;
        try {
            $response_body = $this->_http_client->makeDeleteHttpCall($url);
            if (strpos($response_body, 'true') !== false) {
                $configKey = $this->getCategoryKey($category);
            } else{
                $this->_fbeHelper->log("product set deletion failed!!! ");
            }
        } catch (\Exception $e) {
            $this->_fbeHelper->logException($e);
        }
        return ;
    }
}
