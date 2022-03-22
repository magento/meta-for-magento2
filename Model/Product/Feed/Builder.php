<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Product\Feed;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\Product\Identifier as ProductIdentifier;
use Facebook\BusinessExtension\Model\Config\Source\FeedUploadMethod;
use Facebook\BusinessExtension\Model\Feed\EnhancedCatalogHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Builder\Inventory;
use Facebook\BusinessExtension\Model\Product\Feed\Builder\InventoryInterface;
use Facebook\BusinessExtension\Model\Product\Feed\Builder\Tools as BuilderTools;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class Builder
{
    const ATTR_RETAILER_ID          = 'id';
    const ATTR_ITEM_GROUP_ID        = 'item_group_id';
    const ATTR_DESCRIPTION          = 'description';
    const ATTR_RICH_DESCRIPTION     = 'rich_text_description';
    const ATTR_URL                  = 'link';
    const ATTR_IMAGE_URL            = 'image_link';
    const ATTR_ADDITIONAL_IMAGE_URL = 'additional_image_link';
    const ATTR_BRAND                = 'brand';
    const ATTR_SIZE                 = 'size';
    const ATTR_COLOR                = 'color';
    const ATTR_CONDITION            = 'condition';
    const ATTR_AVAILABILITY         = 'availability';
    const ATTR_INVENTORY            = 'inventory';
    const ATTR_PRICE                = 'price';
    const ATTR_SALE_PRICE           = 'sale_price';
    const ATTR_NAME                 = 'title';
    const ATTR_PRODUCT_TYPE         = 'product_type';
    const ATTR_PRODUCT_CATEGORY     = 'google_product_category';
    const ATTR_UNIT_PRICE           = 'unit_price';

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var string
     */
    protected $defaultBrand;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var BuilderTools
     */
    protected $builderTools;

    /**
     * @var Inventory
     */
    protected $inventory = null;

    /**
     * @var ProductIdentifier
     */
    protected $productIdentifier;

    /**
     * @var EnhancedCatalogHelper
     */
    protected $enhancedCatalogHelper;

    /**
     * @var CatalogHelper
     */
    protected $catalogHelper;

    protected $storeId;

    private $uploadMethod;

    /**
     * @var bool
     */
    private $inventoryOnly = false;

    /**
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param BuilderTools $builderTools
     * @param ProductIdentifier $productIdentifier
     * @param EnhancedCatalogHelper $enhancedCatalogHelper
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        CategoryCollectionFactory $categoryCollectionFactory,
        BuilderTools $builderTools,
        ProductIdentifier $productIdentifier,
        EnhancedCatalogHelper $enhancedCatalogHelper,
        CatalogHelper $catalogHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->builderTools = $builderTools;
        $this->productIdentifier = $productIdentifier;
        $this->enhancedCatalogHelper = $enhancedCatalogHelper;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @param $uploadMethod
     * @return Builder
     */
    public function setUploadMethod($uploadMethod)
    {
        $this->uploadMethod = $uploadMethod;
        return $this;
    }

    /**
     * @param bool $inventoryOnly
     * @return $this
     */
    public function setInventoryOnly($inventoryOnly)
    {
        $this->inventoryOnly = $inventoryOnly;
        return $this;
    }

    /**
     * @return string
     */
    protected function getDefaultBrand()
    {
        if (!$this->defaultBrand) {
            $this->defaultBrand = $this->trimAttribute(self::ATTR_BRAND, $this->fbeHelper->getStoreName());
        }
        return $this->defaultBrand;
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductUrl(Product $product)
    {
        $parentUrl = $product->getParentProductUrl();
        // use parent product URL if a simple product has a parent and is not visible individually
        $url = (!$product->isVisibleInSiteVisibility() && $parentUrl) ? $parentUrl : $product->getProductUrl();
        return $this->builderTools->replaceLocalUrlWithDummyUrl($url);
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getProductImages(Product $product)
    {
        $mainImage = $product->getImage();

        $additionalImages = [];
        if (!empty($product->getMediaGalleryImages())) {
            foreach ($product->getMediaGalleryImages() as $img) {
                if ($img['file'] === $mainImage) {
                    continue;
                }
                $additionalImages[] = $this->builderTools->replaceLocalUrlWithDummyUrl($img['url']);
            }
        }

        return [
            'main_image' => $this->builderTools->replaceLocalUrlWithDummyUrl(
                $this->fbeHelper->getBaseUrlMedia() . 'catalog/product' . $mainImage
            ),
            'additional_images' => array_slice($additionalImages, 0, 10),
        ];
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductPrice(Product $product)
    {
        $price = $this->systemConfig->isPriceInclTax()
            ? $this->catalogHelper->getTaxPrice($product, $product->getPrice(), true)
            : $product->getPrice();
        return $this->builderTools->formatPrice($price, $product->getStoreId());
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductSalePrice(Product $product)
    {
        if (!$product->getSpecialPrice()) {
            return '';
        }

        $price = $this->systemConfig->isPriceInclTax()
            ? $this->catalogHelper->getTaxPrice($product, $product->getSpecialPrice(), true)
            : $product->getSpecialPrice();
        return $this->builderTools->formatPrice($price, $product->getStoreId());
    }

    /**
     * @param Product $product
     * @return string
     * @throws LocalizedException
     */
    protected function getCategoryPath(Product $product)
    {
        $categoryIds = $product->getCategoryIds();
        if (empty($categoryIds)) {
            return '';
        }

        $categoryNames = [];
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('entity_id', $categoryIds)
            ->setOrder('position', 'ASC');
        /** @var CategoryInterface $category */
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName();
        }
        return implode(' > ', $categoryNames);
    }

    /**
     * @param $attrName
     * @param $attrValue
     * @return string
     */
    protected function trimAttribute($attrName, $attrValue)
    {
        $attrValue = trim($attrValue);
        // Facebook Product attributes
        // ref: https://developers.facebook.com/docs/commerce-platform/catalog/fields
        switch ($attrName) {
            case self::ATTR_RETAILER_ID:
            case self::ATTR_URL:
            case self::ATTR_IMAGE_URL:
            case self::ATTR_CONDITION:
            case self::ATTR_AVAILABILITY:
            case self::ATTR_INVENTORY:
            case self::ATTR_PRICE:
            case self::ATTR_SIZE:
            case self::ATTR_COLOR:
                if ($attrValue) {
                    return $attrValue;
                }
                break;
            case self::ATTR_BRAND:
                if ($attrValue) {
                    // brand max size: 70
                    return mb_strlen($attrValue) > 70 ? mb_substr($attrValue, 0, 70) : $attrValue;
                }
                break;
            case self::ATTR_NAME:
                if ($attrValue) {
                    // title max size: 100
                    return mb_strlen($attrValue) > 100 ? mb_substr($attrValue, 0, 100) : $attrValue;
                }
                break;
            case self::ATTR_DESCRIPTION:
                if ($attrValue) {
                    // description max size: 5000
                    return mb_strlen($attrValue) > 5000 ? mb_substr($attrValue, 0, 5000) : $attrValue;
                }
                break;
            case self::ATTR_PRODUCT_TYPE:
                // product_type max size: 750
                if ($attrValue) {
                    return mb_strlen($attrValue) > 750 ?
                        mb_substr($attrValue, mb_strlen($attrValue) - 750, 750) : $attrValue;
                }
                break;
        }
        return '';
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getDescription(Product $product)
    {
        // 'Description' is required by default but can be made
        // optional through the magento admin panel.
        // Try using the short description and title if it doesn't exist.
        $description = $this->trimAttribute(
            self::ATTR_DESCRIPTION,
            $product->getDescription()
        );
        if (!$description) {
            $description = $this->trimAttribute(
                self::ATTR_DESCRIPTION,
                $product->getShortDescription()
            );
        }

        $title = $product->getName();
        $productTitle = $this->trimAttribute(self::ATTR_NAME, $title);

        $description = $description ?: $productTitle;
        // description can't be all uppercase
        $description = $this->builderTools->htmlDecode($description);
        $description = addslashes($this->builderTools->lowercaseIfAllCaps($description));
        return $description;
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getCondition(Product $product)
    {
        $condition = null;
        if ($product->getData('condition')) {
            $condition = $this->trimAttribute(self::ATTR_CONDITION, $product->getAttributeText('condition'));
        }
        return ($condition && in_array($condition, ['new', 'refurbished', 'used'])) ? $condition : 'new';
    }

    /**
     * @param Product $product
     * @param $attribute
     * @return string|false
     */
    private function getCorrectText(Product $product, $attribute)
    {
        if ($product->getData($attribute)) {
            $text = $product->getAttributeText($attribute);
            if (!$text) {
                $text = $product->getData($attribute);
            }
            return $text;
        }
        return false;
    }

    /**
     * @param Product $product
     * @return string|null
     */
    protected function getBrand(Product $product)
    {
        $brand = $this->getCorrectText($product, 'brand');
        if (!$brand) {
            $brand = $this->getCorrectText($product, 'manufacturer');
        }
        if (!$brand) {
            $brand = $this->getDefaultBrand();
        }
        return $this->trimAttribute(self::ATTR_BRAND, $brand);
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getItemGroupId(Product $product)
    {
        $configurableSettings = $product->getConfigurableSettings() ?: [];
        return array_key_exists('item_group_id', $configurableSettings) ? $configurableSettings['item_group_id'] : '';
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getColor(Product $product)
    {
        $configurableSettings = $product->getConfigurableSettings() ?: [];
        return array_key_exists('color', $configurableSettings) ? $configurableSettings['color'] : '';
    }

    /**
     * @param $product
     * @return string
     */
    protected function getSize($product)
    {
        $configurableSettings = $product->getConfigurableSettings() ?: [];
        return array_key_exists('size', $configurableSettings) ? $configurableSettings['size'] : '';
    }

    /**
     * @param $product
     * @return string
     */
    public function getUnitPrice($product)
    {
        return $this->builderTools->getUnitPrice($product);
    }

    /**
     * @param Product $product
     * @return InventoryInterface
     */
    protected function getInventory(Product $product)
    {
        if ($this->inventory === null) {
            $useMultiSource = $this->systemConfig->useMultiSourceInventory($this->storeId);
            $this->inventory = $this->builderTools->getInventoryObject($useMultiSource);
        }
        $this->inventory->initInventoryForProduct($product);
        return $this->inventory;
    }

    /**
     * @param Product $product
     * @return array
     * @throws LocalizedException
     */
    public function buildProductEntry(Product $product)
    {
        $inventory = $this->getInventory($product);
        $retailerId = $this->trimAttribute(
            self::ATTR_RETAILER_ID,
            $this->productIdentifier->getMagentoProductRetailerId($product)
        );

        if ($this->inventoryOnly) {
            return [
                self::ATTR_RETAILER_ID  => $retailerId,
                self::ATTR_AVAILABILITY => $inventory->getAvailability(),
                self::ATTR_INVENTORY    => $inventory->getInventory(),
            ];
        }

        $productType = $this->trimAttribute(self::ATTR_PRODUCT_TYPE, $this->getCategoryPath($product));

        $title = $product->getName();
        $productTitle = $this->trimAttribute(self::ATTR_NAME, $title);

        $images = $this->getProductImages($product);
        $imageUrl = $this->trimAttribute(self::ATTR_IMAGE_URL, $images['main_image']);

        if ($this->uploadMethod === FeedUploadMethod::UPLOAD_METHOD_CATALOG_BATCH_API) {
            $additionalImages = $images['additional_images'];
        } else {
            $additionalImages = implode(',', $images['additional_images']);
        }

        $entry = [
            self::ATTR_RETAILER_ID          => $this->trimAttribute(self::ATTR_RETAILER_ID, $retailerId),
            self::ATTR_ITEM_GROUP_ID        => $this->getItemGroupId($product),
            self::ATTR_NAME                 => $productTitle,
            self::ATTR_DESCRIPTION          => $this->getDescription($product),
            self::ATTR_AVAILABILITY         => $inventory->getAvailability(),
            self::ATTR_INVENTORY            => $inventory->getInventory(),
            self::ATTR_BRAND                => $this->getBrand($product),
            self::ATTR_PRODUCT_CATEGORY     => $product->getGoogleProductCategory() ?? '',
            self::ATTR_PRODUCT_TYPE         => $productType,
            self::ATTR_CONDITION            => $this->getCondition($product),
            self::ATTR_PRICE                => $this->getProductPrice($product),
            self::ATTR_SALE_PRICE           => $this->getProductSalePrice($product),
            self::ATTR_COLOR                => $this->getColor($product),
            self::ATTR_SIZE                 => $this->getSize($product),
            self::ATTR_URL                  => $this->getProductUrl($product),
            self::ATTR_IMAGE_URL            => $imageUrl,
            self::ATTR_ADDITIONAL_IMAGE_URL => $additionalImages,
        ];

        if ($this->uploadMethod === FeedUploadMethod::UPLOAD_METHOD_FEED_API) {
            $entry[self::ATTR_UNIT_PRICE] = $this->getUnitPrice($product);
        }

        $this->enhancedCatalogHelper->assignECAttribute($product, $entry);

        return $entry;
    }

    /**
     * @return array
     */
    public function getHeaderFields()
    {
        $headerFields = [
            self::ATTR_RETAILER_ID,
            self::ATTR_ITEM_GROUP_ID,
            self::ATTR_NAME,
            self::ATTR_DESCRIPTION,
            self::ATTR_AVAILABILITY,
            self::ATTR_INVENTORY,
            self::ATTR_BRAND,
            self::ATTR_PRODUCT_CATEGORY,
            self::ATTR_PRODUCT_TYPE,
            self::ATTR_CONDITION,
            self::ATTR_PRICE,
            self::ATTR_SALE_PRICE,
            self::ATTR_COLOR,
            self::ATTR_SIZE,
            self::ATTR_URL,
            self::ATTR_IMAGE_URL,
            self::ATTR_ADDITIONAL_IMAGE_URL,
        ];

        if ($this->uploadMethod === FeedUploadMethod::UPLOAD_METHOD_FEED_API) {
            $headerFields[] = self::ATTR_UNIT_PRICE;
        }

        if ($this->inventoryOnly) {
            return [self::ATTR_RETAILER_ID, self::ATTR_AVAILABILITY, self::ATTR_INVENTORY];
        }

        return $headerFields;
    }
}
