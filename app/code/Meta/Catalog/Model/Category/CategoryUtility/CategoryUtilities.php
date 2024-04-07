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

namespace Meta\Catalog\Model\Category\CategoryUtility;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as Category;
use Magento\Catalog\Model\Category\Image as CategoryImageService;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Helper\Product\Identifier as ProductIdentifier;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryUtilities
{
    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollection;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var ProductIdentifier
     */
    private ProductIdentifier $productIdentifier;

    /**
     * @var CategoryImageService
     */
    private CategoryImageService $imageService;

    public const CATEGORY_VISIBLE_FOR_META = 'visible';

    public const CATEGORY_HIDDEN_FOR_META = 'hidden';

    private const CATEGORY_HIDDEN_NAME_PREFIX = '[Hidden]';

    private const ROOT_CATEGORY_INDEX_IN_PATH = 1;

    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Constructor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollection
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param ProductIdentifier $productIdentifier
     * @param CategoryImageService $imageService
     * @param EavConfig $eavConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ProductCollectionFactory    $productCollectionFactory,
        CategoryCollectionFactory   $categoryCollection,
        CategoryRepositoryInterface $categoryRepository,
        FBEHelper                   $fbeHelper,
        SystemConfig                $systemConfig,
        ProductIdentifier           $productIdentifier,
        CategoryImageService        $imageService,
        EavConfig                   $eavConfig,
        ResourceConnection          $resourceConnection
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->productIdentifier = $productIdentifier;
        $this->imageService = $imageService;
        $this->eavConfig = $eavConfig;
        $this->resourceConnection = $resourceConnection;
    }
    /**
     * Fetch products for product category
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
     *
     * @param Category $category
     * @param int $storeId
     * @return ProductCollection
     */
    public function getCategoryProducts(Category $category, int $storeId): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStoreId($storeId);
        $productCollection->addAttributeToSelect('sku');
        $productCollection->distinct(true);
        $productCollection->addCategoriesFilter(['eq' => $category->getId()]);
        $productCollection->getSelect()->limit(10000);
        $this->fbeHelper->log("product collection count:" . $productCollection->getSize());

        return $productCollection;
    }

    /**
     * Create filter params for product set api
     *
     * Api link: https://developers.facebook.com/docs/marketing-api/reference/product-set/
     * e.g. {'retailer_id': {'is_any': ['10', '100']}}
     *
     * @param ProductCollection $productCollection
     * @return string
     */
    public function getCategoryProductFilter(ProductCollection $productCollection): string
    {
        $productIdentifier = $this->productIdentifier->getProductIdentifierColName();
        if (!empty($productIdentifier)) {
            $ids = $productCollection->getColumnValues($productIdentifier);
        } else {
            $ids = [];
        }
        $filter = json_encode(['retailer_id' => ['is_any' => $ids]]);

        $this->fbeHelper->log("filter:" . $filter);

        return $filter;
    }

    /**
     * Return Category logger context for be logged
     *
     * @param int $storeId
     * @param string $eventType
     * @param string $flowName
     * @param string $flowStep
     * @param array $extraData
     * @return array
     */
    public function getCategoryLoggerContext(
        int $storeId,
        string $eventType,
        string $flowName,
        string $flowStep,
        array $extraData
    ): array {
        return [
            'store_id' => $storeId,
            'event' => 'category_sync',
            'event_type' => $eventType,
            'flow_name' => $flowName,
            'flow_step' => $flowStep,
            'catalog_id' => $this->systemConfig->getCatalogId($storeId),
            'extra_data' => $extraData
        ];
    }

    /**
     * Get all categories for store
     *
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAllCategoriesForStore(int $storeId, string $flowName, string $traceId): Collection
    {
        $store = $this->systemConfig->getStoreManager()->getStore($storeId);
        $rootCategoryId = $store->getRootCategoryId();
        $rootCategory = $this->categoryRepository->get($rootCategoryId, $storeId);

        return $this->getAllChildrenCategories($rootCategory, $storeId, $flowName, $traceId);
    }

    /**
     * Get all categories
     *
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return Collection
     * @throws LocalizedException
     */
    public function getAllCategoriesForSeller(int $storeId, string $flowName, string $traceId): Collection
    {
        $startTime = $this->fbeHelper->getCurrentTimeInMS();

        $categories = $this->categoryCollection->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter([
                [
                    "attribute" => "path",
                    "like" => Category::TREE_ROOT_ID . "/%"
                ]
            ]);

        $context = $this->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'category_sync_fetch_categories_for_seller',
            [
                'external_trace_id' => $traceId,
                'time_taken_ms' => $this->fbeHelper->getCurrentTimeInMS() - $startTime,
                'num_categories_fetched' => $categories->count()
            ]
        );

        $this->fbeHelper->logTelemetryToMeta(
            sprintf(
                "Fetching categories for seller: storeId: %d, flow: %s",
                $storeId,
                $flowName
            ),
            $context
        );

        return $categories;
    }

    /**
     * Get all children categories
     *
     * Get all children node in category tree recursion is being used.
     *
     * @param Category $category
     * @param int $storeId
     * @param string $flowName
     * @param string $traceId
     * @return Collection
     * @throws LocalizedException
     */
    public function getAllChildrenCategories(
        Category $category,
        int $storeId,
        string $flowName,
        string $traceId
    ): Collection {
        $categoryPath = $category->getPath();
        $startTime = $this->fbeHelper->getCurrentTimeInMS();
        $categories = $this->categoryCollection->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(
                [
                    [
                        "attribute" => "path",
                        "like" => $categoryPath . "/%"
                    ],
                    [
                        "attribute" => "path",
                        "like" => $categoryPath
                    ]
                ]
            );

        $context = $this->getCategoryLoggerContext(
            $storeId,
            '', /* event_type */
            $flowName,
            'category_sync_fetch_categories_for_store',
            [
                'external_trace_id' => $traceId,
                'time_taken_ms' => $this->fbeHelper->getCurrentTimeInMS() - $startTime,
                'num_categories_fetched' => $categories->count(),
                'root_category_id' => $category->getId()
            ]
        );

        $this->fbeHelper->logTelemetryToMeta(
            sprintf(
                "Fetching categories for store: storeId: %d, categoryId: %s, flow: %s",
                $storeId,
                $category->getId(),
                $flowName
            ),
            $context
        );

        return $categories;
    }

    /**
     * Get root category id for given category
     *
     * @param Category $category
     * @return int
     * @throws \Throwable
     */
    public function getRootCategoryIdForCategory(Category $category): int
    {
        $this->fbeHelper->log(sprintf("searching root category for category: %s", $category->getName()));
        $categoryPath = $category->getPath();

        $categoryHierarchy = explode("/", $categoryPath);

        if (count($categoryHierarchy) < 2) {
            throw new LocalizedException(__(
                "No root category found for Category %1 and Category path %2",
                $category->getCategoryId(),
                $categoryPath
            ));
        }

        return (int) $categoryHierarchy[self::ROOT_CATEGORY_INDEX_IN_PATH];
    }

    /**
     * Save key with a fb product set ID
     *
     * @param Category $category
     * @param string $setId
     * @param int $storeId
     * @throws \Throwable
     */
    public function saveFBProductSetID(Category $category, string $setId, $storeId): void
    {
        $this->fbeHelper->log(sprintf(
            "saving product set id for category %s ,id %s ,storeId %s and setId %s",
            $category->getName(),
            $category->getId(),
            $storeId,
            $setId
        ));
        try {
            $productSetAttribute = $this->eavConfig->getAttribute(
                Category::ENTITY,
                SystemConfig::META_PRODUCT_SET_ID
            );
            $productSetAttributeId = $productSetAttribute->getAttributeId();

            if ($productSetAttributeId) {
                $categoryEntityVarcharTable = $this->resourceConnection->getTableName(
                    'catalog_category_entity_varchar'
                );
                if ($category->getData(SystemConfig::META_PRODUCT_SET_ID) == null) {
                    $this->resourceConnection->getConnection()->insert(
                        $categoryEntityVarcharTable,
                        [
                            'attribute_id' => $productSetAttributeId,
                            'store_id' => $storeId,
                            'entity_id' => $category->getId(),
                            'value' => $setId
                        ]
                    );
                } else {
                    $this->resourceConnection->getConnection()->update(
                        $categoryEntityVarcharTable,
                        [
                            'attribute_id' => $productSetAttributeId,
                            'store_id' => $storeId,
                            'entity_id' => $category->getId(),
                            'value' => $setId
                        ],
                        [
                            'attribute_id = ?' => $productSetAttributeId,
                            'store_id = ?' => $storeId,
                            'entity_id = ?' => $category->getId(),
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            $this->fbeHelper->logException($e);
        }
    }

    /**
     * Get category landing page URL.
     *
     * It first tries request path and URL finder for category, if fails to fetch it return category canonical URL
     *
     * @param Category $category
     * @return string
     */
    private function getDirectCategoryURL(Category $category): string
    {
        try {
            // fetch url from getURL function
            $url = $category->getUrl();

            // if url returned by getURL is category admin page url, replace it with category canonical URL
            if (strpos($url, 'admin/catalog/category/view/s') !== false) {
                // if nothing works return URL by category ID (canonical URL)
                $urlKey = $category->getUrlKey() ?? $category->formatUrlKey($category->getName());

                $url = $category->getUrlInstance()->getDirectUrl(
                    sprintf('catalog/category/view/s/%s/id/%s/', $urlKey, $category->getId())
                );

                $this->fbeHelper->log(sprintf(
                    "Category canonical URL used for category: %s, url: %s",
                    $category->getName(),
                    $url
                ));
                return $url;
            }

            $this->fbeHelper->log(sprintf(
                "Category getURL function used for category: %s, url: %s",
                $category->getName(),
                $url
            ));
            return $url;
        } catch (\Throwable $e) {
            $this->fbeHelper->logException($e);
            return '';
        }
    }

    /**
     * Get category MetaData for FB product set
     *
     * @param Category $category
     * @return array|null
     */
    public function getCategoryMetaData(Category $category): ?array
    {
        try {
            $categoryImageURL = $this->imageService->getUrl($category);
        } catch (\Throwable $e) {
            $categoryImageURL = null;
            $this->fbeHelper->logException($e);
        }

        $categoryURL = $this->getDirectCategoryURL($category);

        // check if url path exist use it, otherwise url_key
        $categoryURLHandle = empty($category->getData('url_path'))
            ? $category->getUrlKey() : $category->getData('url_path');

        return [
            'cover_image_url' => $categoryImageURL,
            'external_url' => $categoryURL,
            'external_url_handle' => $categoryURLHandle
        ];
    }

    /**
     * Get category path name
     *
     * If the category is Tops we might create "Default Category > Men > Tops"
     *
     * @param Category $category
     * @param int $storeId
     * @param bool $isVisibleOnMeta
     * @return string
     */
    public function getCategoryPathName(Category $category, int $storeId, bool $isVisibleOnMeta): string
    {
        $categoryPath = (string)$category->getPath();

        $categoryName = implode(" > ", array_filter(array_map(
            function ($innerId) use ($storeId) {
                try {
                    $innerCategory = $this->categoryRepository->get($innerId, $storeId);
                    // parent of root category
                    if ($innerCategory->getLevel() == 0) {
                        return null;
                    }
                    return $innerCategory->getName();
                } catch (\Throwable $e) {
                    return null;
                }
            },
            explode("/", $categoryPath)
        )));

        if (!$isVisibleOnMeta) {
            $categoryName = self::CATEGORY_HIDDEN_NAME_PREFIX . $categoryName;
        }

        return $categoryName;
    }

    /**
     * Checks visibility based on sync with Meta flag,active status and linked to configured store
     *
     * @param Category $category
     * @param bool $isLinkedToStore
     * @return bool
     */
    public function isCategoryVisibleOnMeta(Category $category, bool $isLinkedToStore): bool
    {
        $syncEnabled = $category->getData(SystemConfig::CATEGORY_SYNC_TO_FACEBOOK);
        $isActive = $category->getIsActive();
        if ($syncEnabled === '0' || !$isActive || !$isLinkedToStore) {
            return false;
        }
        return true;
    }

    /**
     * Return only the set of store ids which have FBE installed (working token, etc...)
     *
     * @return array
     */
    public function getAllFBEInstalledStoreIds(): array
    {
        return array_map(
            function ($store) {
                return (int)$store->getId();
            },
            $this->systemConfig->getAllFBEInstalledStores()
        );
    }
}
