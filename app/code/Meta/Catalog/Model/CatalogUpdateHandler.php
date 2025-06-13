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

namespace Meta\Catalog\Model;

use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Helper\Product\Identifier as ProductIdentifier;
use Meta\Catalog\Model\ResourceModel\FacebookCatalogUpdate as FBCatalogUpdateResourceModel;
use Meta\Catalog\Model\ResourceModel\FacebookCatalogUpdate\Collection;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\Catalog\Model\Product\Feed\Method\BatchApi;
use Magento\Catalog\Model\ProductRepository as MagentoProductRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogUpdateHandler
{
    /**
     * @var FBCatalogUpdateResourceModel
     */
    private FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphApiAdapter;

    /**
     * @var BatchApi
     */
    private BatchApi $batchApi;

    /**
     * @var ProductIdentifier
     */
    private ProductIdentifier $productIdentifier;

    /**
     * @var MagentoProductRepository
     */
    private MagentoProductRepository $magentoProductRepository;

    /**
     * CatalogUpdateConsumer constructor
     *
     * @param FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param ProductRepository $productRepository
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param BatchApi $batchApi
     * @param ProductIdentifier $productIdentifier
     * @param MagentoProductRepository $magentoProductRepository
     */
    public function __construct(
        FBCatalogUpdateResourceModel $fbCatalogUpdateResourceModel,
        FBEHelper                    $fbeHelper,
        SystemConfig                 $systemConfig,
        ProductRepository            $productRepository,
        GraphAPIAdapter              $graphAPIAdapter,
        BatchApi                     $batchApi,
        ProductIdentifier            $productIdentifier,
        MagentoProductRepository     $magentoProductRepository
    ) {
        $this->fbCatalogUpdateResourceModel = $fbCatalogUpdateResourceModel;
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->productRepository = $productRepository;
        $this->graphApiAdapter = $graphAPIAdapter;
        $this->batchApi = $batchApi;
        $this->productIdentifier = $productIdentifier;
        $this->magentoProductRepository = $magentoProductRepository;
    }

    /**
     * Process update message
     *
     * @return bool
     */
    public function executeUpdate()
    {
        return $this->execute('update');
    }

    /**
     * Process delete message
     *
     * @return bool
     */
    public function executeDelete()
    {
        return $this->execute('delete');
    }

    /**
     * Process message
     *
     * @param string $method
     * @return bool
     */
    private function execute(string $method)
    {
        $batchId = $this->fbCatalogUpdateResourceModel->getUniqueBatchId();
        try {
            $this->fbCatalogUpdateResourceModel->reserveProductsForBatchId($method, $batchId);
            $productUpdates = $this->fbCatalogUpdateResourceModel->getReservedProducts($batchId);
            if ($productUpdates->getSize() <= 0) {
                return true;
            }

            $stores = $this->systemConfig->getAllFBEInstalledStores();
            foreach ($stores as $store) {
                $catalogId = $this->setupConfigs($store->getId());
                $storeId = (int)$store->getId();

                if (!$catalogId) {
                    continue;
                }
                if ($method === 'update') {
                    $this->processUpdates($productUpdates, $storeId, $catalogId);
                } elseif ($method === 'delete') {
                    $this->processDeletes($productUpdates, $catalogId);
                }
            }
        } catch (\Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'event' => 'catalog_update_handler',
                    'event_type' => $method
                ]
            );
            $this->fbCatalogUpdateResourceModel->clearBatchId($batchId);
            return false;
        }
        return true;
    }

    /**
     * Setup and get configurations for catalog batch upload
     *
     * @param int $storeId
     * @return string|bool
     */
    private function setupConfigs($storeId)
    {
        $isCatalogSyncEnabled = $this->systemConfig->isCatalogSyncEnabled($storeId);

        if (!($isCatalogSyncEnabled)) {
            return false;
        }

        $debugMode = $this->systemConfig->isDebugMode($storeId);
        $catalogId = $this->systemConfig->getCatalogId($storeId);
        $accessToken = $this->systemConfig->getAccessToken($storeId);

        if (!($catalogId && $accessToken)) {
            return false;
        }

        $this->graphApiAdapter->setDebugMode($debugMode)
            ->setAccessToken($accessToken);

        return $catalogId;
    }

    /**
     * Process product batch
     *
     * @param Collection $productUpdates
     * @param int $storeId
     * @param string $catalogId
     * @return void
     * @throws GuzzleException
     */
    private function processUpdates(Collection $productUpdates, $storeId, string $catalogId)
    {
        $productIds = array_map(fn($productUpdate) => $productUpdate->getProductId(), $productUpdates->getItems());
        $parentProducts = $this->productRepository->getParentProducts($productIds, $storeId);
        $products = $this->productRepository->getCollection($productIds, $storeId);
        $productLinks = $this->productRepository->getParentProductLink($productIds);

        $requestData = [];
        foreach ($products as $product) {
            try {
                if (isset($productLinks[$product->getId()])) {
                    $parentProduct = $parentProducts->getItemById($productLinks[$product->getId()]);
                    if ($parentProduct) {
                        $product = $this->productRepository->loadParentProductData($product, $parentProduct);
                    } else {
                        try {
                            $parentProduct = $this->magentoProductRepository->getById($productLinks[$product->getId()]);
                            $product = $this->productRepository->loadParentProductData($product, $parentProduct);
                        } catch (\Exception $e) {
                            $this->fbeHelper->logException(
                                $e,
                                [
                                    'log_type' => 'error',
                                    'error_description' => 'Exception occured during loading parent product data',
                                    'product_id' => $product->getId(),
                                    'parent_product_id' => $productLinks[$product->getId()]
                                ]
                            );
                        }
                    }
                }

                $syncWithMeta = $product->getSendToFacebook();
                // null value should be treated as syncWithMeta enabled
                if (!$syncWithMeta && $syncWithMeta !== null) {
                    $identifier = $this->productIdentifier->getMagentoProductRetailerId($product);
                    if ($identifier) {
                        $requestData[] = $this->batchApi->buildDeleteProductRequest($identifier);
                    }
                } else {
                    $requestData[] = $this->batchApi->buildRequestForIndividualProduct($product);
                }
            } catch (\Throwable $e) {
                $this->fbeHelper->logException($e);
                continue;
            }
        }
        if (!empty($requestData)) {
            $this->graphApiAdapter->catalogBatchRequest($catalogId, $requestData);
        }
    }

    /**
     * Process product batch delete
     *
     * @param Collection $productUpdates
     * @param string $catalogId
     * @return void
     * @throws GuzzleException
     */
    private function processDeletes(Collection $productUpdates, string $catalogId)
    {
        $requestData = [];
        foreach ($productUpdates as $productUpdate) {
            // Can not use productIdentifier->getMagentoProductRetailerId(),
            // since it does not take FacebookCatalogUpdate Object
            if ($this->productIdentifier->getProductIdentifierColName() === 'sku') {
                $identifier = $productUpdate->getSku();
            } else {
                $identifier = $productUpdate->getProductId();
            }

            if ($identifier) {
                $requestData[] = $this->batchApi->buildDeleteProductRequest($identifier);
            }
        }
        if (!empty($requestData)) {
            $this->graphApiAdapter->catalogBatchRequest($catalogId, $requestData);
        }
    }
}
