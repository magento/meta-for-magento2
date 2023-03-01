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

namespace Meta\Catalog\Block\Adminhtml;

use Exception;
use Magento\Store\Api\Data\StoreInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Block\Template;
use Psr\Log\LoggerInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * @api
 */
class Diagnostics extends Template
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreRepositoryInterface
     */
    public $storeRepo;

    /**
     * Construct
     *
     * @param Context $context
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param StoreRepositoryInterface $storeRepo
     * @param array $data
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        FBEHelper $fbeHelper,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        StoreRepositoryInterface $storeRepo,
        array $data = []
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeRepo = $storeRepo;
        parent::__construct($context, $data);
    }

    /**
     * Get reports
     *
     * @return array
     */
    public function getReports()
    {
        $reports = [];
        $stores = $this->getStores();
        try {
            foreach ($stores as $key => $store) {
                if ($key === 'admin') {
                    continue;
                }
                $catalogId = $this->systemConfig->getCatalogId($store->getId());
                $response = $this->graphApiAdapter->getCatalogDiagnostics($catalogId);
                if (isset($response['diagnostics']['data'])) {
                    $reports[$key] = [];
                    $reports[$key]['data'] = $response['diagnostics']['data'];
                    $reports[$key]['catalog_id'] = $catalogId;
                    $reports[$key]['store_id'] = $store->getId();
                    $reports[$key]['store_name'] = $store->getName();
                }
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        return $reports;
    }

    /**
     * Get sample affected items
     *
     * @param array $diagnosticItem
     * @param int $catalogId
     * @param int $storeId
     * @return array
     */
    public function getSampleAffectedItems(array $diagnosticItem, int $catalogId, int $storeId)
    {
        if (!array_key_exists('sample_affected_items', $diagnosticItem)) {
            return [];
        }

        try {
            $fbIds = array_map(function ($a) {
                return $a['id'];
            }, $diagnosticItem['sample_affected_items']);

            $fbProducts = $this->graphApiAdapter->getProductsByFacebookProductIds($catalogId, $fbIds);
            $retailerIds = array_map(function ($a) {
                return $a['retailer_id'];
            }, $fbProducts['data']);

            return $this->getProducts($retailerIds, $storeId);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return [];
    }

    /**
     * Get admin url
     *
     * @param ProductInterface $product
     * @param int $store
     * @return string
     */
    public function getAdminUrl(ProductInterface $product, int $store = null)
    {
        $params = ['id' => $product->getId()];
        if ($store) {
            $params['store'] = $store;
        }
        return $this->getUrl('catalog/product/edit', $params);
    }

    /**
     * Get products
     *
     * @param array $retailerIds
     * @param int $storeId
     * @return array
     */
    private function getProducts(array $retailerIds, int $storeId)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        $productIdentifierAttr = $this->systemConfig->getProductIdentifierAttr($storeId);
        if ($productIdentifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            $collection->addAttributeToFilter('sku', ['in' => $retailerIds]);
        } elseif ($productIdentifierAttr === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            $collection->addIdFilter($retailerIds);
        } else {
            return [];
        }

        return $collection->getItems();
    }

    /**
     * Get stores
     *
     * @return StoreInterface[]
     */
    private function getStores()
    {
        return $this->storeRepo->getList();
    }
}
