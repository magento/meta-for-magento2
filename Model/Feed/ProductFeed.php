<?php /** @noinspection PhpUndefinedFieldInspection */
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Feed;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Zend_Locale_Data;
use Zend_Locale_Exception;

// Introduce all the helper method used here in FBEHelper class
class ProductFeed
{
    const ATTR_RETAILER_ID = 'id';
    const ATTR_DESCRIPTION = 'description';
    //const ATTR_URL = 'url';
    const ATTR_URL = 'link';
    //const ATTR_IMAGE_URL = 'image_url';
    const ATTR_IMAGE_URL = 'image_link';
    const ATTR_BRAND = 'brand';
    const ATTR_CONDITION = 'condition';
    const ATTR_AVAILABILITY = 'availability';
    const ATTR_PRICE = 'price';
    const ATTR_METHOD = 'method';
    const ATTR_CREATE = 'CREATE';
    const ATTR_UPDATE = 'UPDATE';
    const ATTR_DELETE = 'DELETE';
    const ATTR_DATA = 'data';
    const ATTR_NAME = 'title';
    const ATTR_CURRENCY = 'currency';
    const ATTR_CATEGORY = 'category';
    const ATTR_PRODUCT_TYPE = 'product_type';

    // Process only the maximum allowed by API per request
    const BATCH_MAX = 4999;

    const PRICE_PRECISION = 2;

    /**
     * @var FBEHelper
     */
    protected $_fbeHelper;

    protected $base_currency;

    protected $current_currency;

    /**
     * Constructor
     * @param FBEHelper $helper
     */
    public function __construct(FBEHelper $helper)
    {
        $this->_fbeHelper = $helper;
    }

    protected function isValidCondition($condition)
    {
        return ($condition &&
            ($condition === 'new' ||
                $condition === 'used' ||
                $condition === 'refurbished'));
    }

    protected function defaultBrand()
    {
        if (!isset($this->defaultBrand)) {
            $this->defaultBrand = $this->buildProductAttr(self::ATTR_BRAND, $this->_fbeHelper->getStoreName());
        }
        return $this->defaultBrand;
    }

    protected function defaultCondition()
    {
        return $this->buildProductAttr(self::ATTR_CONDITION, 'new');
    }

    protected function isValidUrl($product_link)
    {
        return
            // This can fail for non unicode links.
            filter_var($product_link, FILTER_VALIDATE_URL) ||
            mb_substr($product_link, 0, 4) === 'http';
    }

    private function getProductImages(Product $product)
    {
        $mainImage = $product->getImage();
        $additionalImages = [];

        foreach ($product->getMediaGalleryImages() as $img) {
            if ($img['file'] === $mainImage) continue;
        }
        return [
            'main_image' => $this->_fbeHelper->getBaseUrlMedia() . 'catalog/product' . $mainImage,
            'additional_images' => array_slice($additionalImages, 0, 10)
        ];
    }

    private function stripCurrencySymbol($price)
    {
        if (!isset($this->currency_strip_needed)) {
            $this->currency_strip_needed = !preg_match('/^[0-9,.]*$/', $price);
        }
        if ($this->currency_strip_needed) {
            return preg_replace('/[^0-9,.]/', '', $price);
        } else {
            return $price;
        }
    }

    private function getCorrectText(Product $product, $column, $attribute)
    {
        if ($product->getData($attribute)) {
            $text = $this->buildProductAttr($column, $product->getAttributeText($attribute));
            if (!$text) {
                $text = $this->buildProductAttr($column, $product->getData($attribute));
            }
            return $text;
        }
        return null;
    }

    private function lowercaseIfAllCaps($string)
    {
        // if contains lowercase, don't update string
        if (!preg_match('/[a-z]/', $string)) {
            if (mb_strtoupper($string, 'utf-8') === $string) {
                return mb_strtolower($string, 'utf-8');
            }
        }
        return $string;
    }

    public function isCurrencyConversionNeeded()
    {
        if ($this->group_separator !== ',' && $this->group_separator !== '.') {
            return true;
        } else if ($this->decimal_separator !== ',' &&
            $this->decimal_separator !== '.') {
            return true;
        } else {
            return false;
        }
    }

    private function getConfigurableProductPrice(Product $product)
    {
        if ($product->getFinalPrice() === 0) {
            $configurable = $this->_fbeHelper->createObject(ConfigurableType::class);
            if ($configurable != null && !empty($configurable->getUsedProductCollection($product))) {
                $simple_collection = $configurable->getUsedProductCollection($product)
                    ->addAttributeToSelect('price')->addFilterByRequiredOptions();
                foreach ($simple_collection as $simple_product) {
                    if ($simple_product->getPrice() > 0) {
                        return $this->getFinalPrice($simple_product);
                    }
                }
            }
        }
        return $this->getFinalPrice($product);
    }

    private function getGroupedProductPrice(Product $product)
    {
        $assoc_products = $product->getTypeInstance()
            ->getAssociatedProductCollection($product)
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('tax_class_id')
            ->addAttributeToSelect('tax_percent');

        $min_price = INF;
        foreach ($assoc_products as $assoc_product) {
            $min_price = min($min_price, $this->getFinalPrice($assoc_product));
        }
        return $min_price;
    }

    private function getBundleProductPrice($product)
    {
        return $product->getPriceModel()->getTotalPrices($product, 'min', 1, 1);
    }

    private function getFinalPrice($product, $price = null)
    {
        if (!isset($this->taxHelper)) {
            $this->taxHelper = $this->_fbeHelper->getObject(\Magento\Catalog\Helper\Data::class);
        }
        if ($price === null) {
            $price = $product->getFinalPrice();
        }
        if ($price === null) {
            $price = $product->getData('special_price');
        }
        return $this->taxHelper->getTaxPrice($product, $price, true);
    }

    private function getProductPrice($product)
    {
        $price = 0;
        switch ($product->getTypeId()) {
            case 'configurable':
                $price = $this->getConfigurableProductPrice($product);
                break;
            case 'grouped':
                $price = $this->getGroupedProductPrice($product);
                break;
            case 'bundle':
                $price = $this->getBundleProductPrice($product);
                break;
            default:
                $price = $this->getFinalPrice($product);
                break;
        }

        if (!isset($this->base_currency)) {
            $this->base_currency = $this->_fbeHelper->getStore($this->store_id)->getBaseCurrencyCode();
        }
        if (!isset($this->current_currency)) {
            $this->current_currency =
                $this->_fbeHelper->getStore($this->store_id)->getCurrentCurrencyCode();
        }
        if ($this->base_currency === $this->current_currency) {
            return $price;
        }

        if (!isset($this->currency_rate)) {
            $this->currency_rate = $this->_fbeHelper
                ->createObject('Magento\Directory\Model\Currency')
                ->getCurrencyRates(
                    $this->base_currency, array($this->current_currency));
            $this->currency_rate =
                is_array($this->currency_rate) ? end($this->currency_rate) : 0;
        }

        if (!$this->currency_rate || is_nan($this->currency_rate)) {
            $this->_fbeHelper->log("ERROR : Currency Conversion Rate Is 0/Infinity.");
            throw new Exception(
                "ERROR : Currency Conversion Rate Is 0/Infinity.\n" .
                "Failed when converting " . $this->base_currency . " to " . $this->current_currency .
                " getCurrencyRate() returned " . ($this->currency_rate ?: " NULL") . "\n" .
                " This can be fixed by setting your currency rates in " .
                "System > Currency > Rates"
            );
        } else {
            return $this->_fbeHelper
                ->getObject('Magento\Directory\Helper\Data')
                ->currencyConvert(
                    $price,
                    $this->base_currency,
                    $this->current_currency);
        }
    }

    private function processAttrValue($attr_value, $escapefn)
    {
        $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        $attr_value = $this->htmlDecode($attr_value);
        $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        return trim($attr_value);
    }

    protected function htmlDecode($attr_value)
    {
        return strip_tags(html_entity_decode(($attr_value)));
    }

    private function convertCurrency($price)
    {
        $price = str_replace($this->group_separator, '', $price);
        $price = str_replace($this->decimal_separator, '.', $price);
        return $price;
    }

    protected function buildProductAttr($attribute, $value)
    {
        return $this->buildProductAttrText($attribute, $value);
    }

    private function getCategoryPath($product)
    {
        $category_names = [];
        $category_ids = $product->getCategoryIds();
        foreach ($category_ids as $category_id) {
            if (array_key_exists($category_id, $this->categoryNameMap)) {
                $category_names[] = $this->categoryNameMap[$category_id];
            }
        }
        return implode(" | ", $category_names);
    }

    // Generates a map of the form : 4 => "Root > Mens > Shoes"
    private function generateCategoryNameMap()
    {
        $categories = $this->_fbeHelper
            ->getObject(CategoryCollection::class)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('path')
            ->addAttributeToSelect('is_active')
            ->addAttributeToFilter('is_active', 1);
        $name = [];
        $breadcrumb = [];
        foreach ($categories as $category) {
            $entity_id = $category->getId();
            $name[$entity_id] = $category->getName();
            $breadcrumb[$entity_id] = $category->getPath();
        }
        // Converts the product category paths to human readable form.
        // e.g.  "1/2/3" => "Root > Mens > Shoes"
        foreach ($name as $id => $value) {
            $breadcrumb[$id] = implode(" > ", array_filter(array_map(
                function ($inner_id) use (&$name) {
                    return isset($name[$inner_id]) ? $name[$inner_id] : null;
                },
                explode("/", $breadcrumb[$id]))));
        }
        return $breadcrumb;
    }

    protected function buildProductAttrText(
        $attr_name,
        $attr_value,
        $escapefn = null)
    {
        // Facebook Product BATCH API attributes
        // ref: https://developers.facebook.com/docs/marketing-api/catalog-batch
        switch ($attr_name) {
            case self::ATTR_RETAILER_ID:
            case self::ATTR_URL:
            case self::ATTR_IMAGE_URL:
            case self::ATTR_CONDITION:
            case self::ATTR_AVAILABILITY:
            case self::ATTR_PRICE:
                if ((bool)$attr_value) {
                    $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
                    return trim($attr_value);
                }
                break;
            case self::ATTR_BRAND:
                if ((bool)$attr_value) {
                    $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
                    $attr_value = trim($attr_value);
                    // brand max size: 70
                    if (mb_strlen($attr_value) > 70) {
                        $attr_value = mb_substr($attr_value, 0, 70);
                    }
                    return $attr_value;
                }
                break;
            case self::ATTR_NAME:
                if ((bool)$attr_value) {
                    $attr_value = $this->processAttrValue($attr_value, $escapefn);
                    // title max size: 100
                    if (mb_strlen($attr_value) > 100) {
                        $attr_value = mb_substr($attr_value, 0, 100);
                    }
                    return $attr_value;
                }
                break;
            case self::ATTR_DESCRIPTION:
                if ((bool)$attr_value) {
                    $attr_value = $this->processAttrValue($attr_value, $escapefn);
                    // description max size: 5000
                    if (mb_strlen($attr_value) > 5000) {
                        $attr_value = mb_substr($attr_value, 0, 5000);
                    }
                    return $attr_value;
                }
                break;
            case self::ATTR_PRODUCT_TYPE:
                // product_type max size: 750
                if ((bool)$attr_value) {
                    $attr_value = $this->processAttrValue($attr_value, $escapefn);
                    if (mb_strlen($attr_value) > 750) {
                        $attr_value = mb_substr($attr_value, mb_strlen($attr_value) - 750, 750);
                    }
                    return $attr_value;
                }
                break;
        }
        return '';
    }

    /**
     * @param Product $product
     * @param string $method
     * @return array
     * @throws Zend_Locale_Exception
     */
    public function buildProductRequest(Product $product, $method = self::ATTR_UPDATE)
    {
        $locale_code = $this->_fbeHelper->getCurrentStorefrontLocaleCode();
        $symbols = Zend_Locale_Data::getList($locale_code, 'symbols');
        $this->group_separator = $symbols['group'];
        $this->decimal_separator = $symbols['decimal'];
        $this->conversion_needed = $this->isCurrencyConversionNeeded();
        $this->store_url = $this->_fbeHelper->getBaseUrl();
        $this->store_id = $this->_fbeHelper->getDefaultStoreId();
        $this->currency_strip_needed = true;
        $this->categoryNameMap = $this->generateCategoryNameMap();

        $request = [];
        /** @var Item $stock */
        $stock = $this->_fbeHelper->createObject(StockItemRepository::class)->get($product->getId());

        $request[self::ATTR_METHOD] = $method;
        $this->dedup_ids[$product->getId()] = true;

        $brand = $this->getCorrectText($product, self::ATTR_BRAND, 'brand');
        if (!$brand) {
            $brand = $this->getCorrectText($product, self::ATTR_BRAND, 'manufacturer');
        }

        $productType = $this->buildProductAttr(self::ATTR_PRODUCT_TYPE, $this->getCategoryPath($product));

        // 'Description' is required by default but can be made
        // optional through the magento admin panel.
        // Try using the short description and title if it doesn't exist.
        $description = $this->buildProductAttr(
            self::ATTR_DESCRIPTION,
            $product->getDescription());
        if (!$description) {
            $description = $this->buildProductAttr(
                self::ATTR_DESCRIPTION,
                $product->getShortDescription());
        }

        $title = $product->getName();
        $product_title = $this->buildProductAttr(self::ATTR_NAME, $title);

        $description = $description ?: $product_title;
        // description can't be all uppercase
        $description = addslashes($this->lowercaseIfAllCaps($description));

        $price = sprintf('%s %s', $this->getProductPrice($product), $this->current_currency);

        if ($this->conversion_needed) {
            $price = $this->convertCurrency($price);
        }

        //$request_data[self::ATTR_CURRENCY] = $this->_fbeHelper->getStore($this->store_id)->getCurrentCurrencyCode();

        $condition = null;
        if ($product->getData('condition')) {
            $condition = $this->buildProductAttr(self::ATTR_CONDITION, $product->getAttributeText('condition'));
        }
        $condition = $this->isValidCondition($condition) ? $condition : $this->defaultCondition();

        // @todo fix
        $product_link = $product->getProductUrl();
        if (!$this->isValidUrl($product_link)) {
            $product_link = $this->store_url . $product_link;
        }

        $url = $this->buildProductAttr(self::ATTR_URL, $product_link);

        $images = $this->getProductImages($product);
        $imageUrl = $this->buildProductAttr(self::ATTR_IMAGE_URL, $images['main_image']);

        $request_data = [
            self::ATTR_RETAILER_ID => $this->buildProductAttr(self::ATTR_RETAILER_ID, $product->getSku()),
            self::ATTR_NAME => $product_title,
            self::ATTR_PRICE => $price,
            // @todo add sale price
            // @todo add additional product images
            self::ATTR_AVAILABILITY => $this->buildProductAttr(self::ATTR_AVAILABILITY, $stock->getIsInStock() ? 'in stock' : 'out of stock'),
            self::ATTR_BRAND => $brand ?: $this->defaultBrand(),
            self::ATTR_CONDITION => $condition,
            //'google_product_category' => 'Apparel & Accessories > Clothing > Underwear & Socks',
            'inventory' => $stock->getQty(),
            self::ATTR_PRODUCT_TYPE => $productType,
            self::ATTR_DESCRIPTION => $description,
            self::ATTR_URL => $url,
            'additional_image_link' => $images['additional_images'],
            self::ATTR_IMAGE_URL => $imageUrl,

            //self::ATTR_CATEGORY => $productType,
        ];

        if ($product->getConfigurableSettings()) {
            foreach ($product->getConfigurableSettings() as $key => $value) {
                $request_data[$key] = $value;
            }
        }

        $request[self::ATTR_DATA] = $request_data;

        return $request;
    }

    protected function getProductCollection()
    {
        $storeId = $this->_fbeHelper->getDefaultStoreId();
        /** @var ProductCollection $collection */
        $collection = $this->_fbeHelper->createObject(ProductCollection::class);
        $collection->addAttributeToSelect('*')
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addUrlRewrite();
        return $collection;
    }

    /**
     * @param Product $product
     * @param array $requests
     * @throws Zend_Locale_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    protected function processConfigurableProduct(Product $product, array &$requests)
    {
        /** @var ConfigurableType $configurableType */
        $configurableType = $product->getTypeInstance();
        $attributes = $configurableType->getConfigurableAttributes($product);

        foreach ($configurableType->getUsedProducts($product) as $childProduct) {
            /** @var Product $childProduct */
            $configurableSettings = ['item_group_id' => $product->getSku()];
            foreach ($attributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeCode = $productAttribute->getAttributeCode();
                $attributeValue = $childProduct->getData($productAttribute->getAttributeCode());
                $attributeLabel = $productAttribute->getSource()->getOptionText($attributeValue);
                $configurableSettings[$attributeCode] = $attributeLabel;
            }
            $childProduct->setConfigurableSettings($configurableSettings);
            /** @var Product $item */
            $entry = $this->buildProductRequest($childProduct);
            array_push($requests, $entry);
        }
        return $this;
    }

    public function generateProductRequestData($access_token)
    {
        $count = 0;
        $batch_max = self::BATCH_MAX;

        $skip_count = 0;
        $exception_count = 0;

        $collection = $this->getProductCollection();
        $totalProductCount = $collection->getSize();

        $this->dedup_ids = [];

        $products_pushed = [];
        while ($count < $totalProductCount) {
            $requests = [];
            /** @var ProductCollection $productCollection */
            $productCollection = clone $collection;
            $products = $productCollection
                ->setPageSize($batch_max)
                ->setCurPage($count / $batch_max + 1);

            foreach ($products as $product) {
                /** @var Product $product */
                try {
                    if ($product->getId() && $product->getName() && !isset($this->dedup_ids[$product->getId()])) {
                        // handle product variants
                        if ($product->getTypeId() === ConfigurableType::TYPE_CODE) {
                            $this->processConfigurableProduct($product, $requests);
                        } else {
                            $entry = $this->buildProductRequest($product);
                            array_push($requests, $entry);
                        }
                    } else {
                        $skip_count++;
                    }
                } catch (Exception $e) {
                    $exception_count++;
                    // Don't overload the logs, log the first 3 exceptions.
                    if ($exception_count <= 3) {
                        $this->_fbeHelper->logException($e);
                    }
                    // If it looks like a systemic failure : stop feed generation.
                    if ($exception_count > 100) {
                        throw $e;
                    }
                }
                $product->clearInstance();
            }

            try {
                if (!empty($requests)) {
                    $this->_fbeHelper->log(sprintf("Pushing %d Products of %d", sizeof($requests), $totalProductCount));
                    $response = $this->_fbeHelper->makeHttpRequest($requests, $access_token);
                    $this->_fbeHelper->log("Product push response " . json_encode($response));
                    array_push($products_pushed, $response);
                }
            } catch (HttpException $e) {
                $this->_fbeHelper->logException($e);
            }
            unset($products);
            unset($requests);
            $count += $batch_max;
        }

        if ($skip_count != 0) {
            $this->_fbeHelper->log(sprintf('Skipped %d products', $skip_count));
        }

        if ($exception_count != 0) {
            $this->_fbeHelper->log("Exceptions in Feed push : " . $exception_count);
        }

        return $products_pushed;
    }
}
