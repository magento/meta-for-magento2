<?php /** @noinspection PhpUndefinedFieldInspection */
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
namespace Meta\Catalog\Model\Product\Feed\Method;

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Builder;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Configurable as ConfigurableProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Simple as SimpleProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;

class BatchApi
{
    const ATTR_METHOD = 'method';
    const ATTR_UPDATE = 'UPDATE';
    const ATTR_DATA = 'data';

    // Process only the maximum allowed by API per request
    const BATCH_MAX = 4999;

    protected $storeId;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var ProductRetrieverInterface[]
     */
    protected $productRetrievers;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @param FBEHelper $helper
     * @param GraphAPIAdapter $graphApiAdapter
     * @param SystemConfig $systemConfig
     * @param SimpleProductRetriever $simpleProductRetriever
     * @param ConfigurableProductRetriever $configurableProductRetriever
     * @param Builder $builder
     */
    public function __construct(
        FBEHelper $helper,
        GraphAPIAdapter $graphApiAdapter,
        SystemConfig $systemConfig,
        SimpleProductRetriever $simpleProductRetriever,
        ConfigurableProductRetriever $configurableProductRetriever,
        Builder $builder
    ) {
        $this->fbeHelper = $helper;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->systemConfig = $systemConfig;
        $this->productRetrievers = [
            $simpleProductRetriever,
            $configurableProductRetriever
        ];
        $this->builder = $builder;
    }

    /**
     * @param Product $product
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    protected function buildProductRequest(Product $product, $method = self::ATTR_UPDATE)
    {
        return [
            self::ATTR_METHOD => $method,
            self::ATTR_DATA => $this->builder->buildProductEntry($product)
        ];
    }

    /**
     * @param Product $product
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    public function buildRequestForIndividualProduct(Product $product, $method = self::ATTR_UPDATE)
    {
        $this->builder->setStoreId($product->getStoreId());
        return $this->buildProductRequest($product, $method);
    }

    /**
     * @param null $storeId
     * @param null $accessToken
     * @param bool $inventoryOnly
     * @return array
     * @throws Exception
     */
    public function generateProductRequestData($storeId = null, $accessToken = null, $inventoryOnly = false)
    {
        $this->fbeHelper->log($storeId ? "Starting batch upload for store $storeId" : 'Starting batch upload');

        $this->storeId = $storeId;
        $this->builder->setStoreId($storeId)
            ->setInventoryOnly($inventoryOnly);
        $this->graphApiAdapter->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($accessToken ?? $this->systemConfig->getAccessToken($storeId));

        $catalogId = $this->systemConfig->getCatalogId($storeId);

        $currentBatch = 1;
        $requests = [];
        $responses = [];
        $exceptions = 0;
        foreach ($this->productRetrievers as $productRetriever) {
            $offset = 0;
            $productRetriever->setStoreId($storeId);
            $limit = $productRetriever->getLimit();
            do {
                $products = $productRetriever->retrieve($offset);
                $offset += $limit;
                if (empty($products)) {
                    break;
                }

                foreach ($products as $product) {
                    try {
                        $requests[] = $this->buildProductRequest($product);
                    } catch (Exception $e) {
                        $exceptions++;
                        // Don't overload the logs, log the first 3 exceptions
                        if ($exceptions <= 3) {
                            $this->fbeHelper->logException($e);
                        }
                        // If it looks like a systemic failure : stop feed generation
                        if ($exceptions > 100) {
                            throw $e;
                        }
                    }

                    if (!empty($requests) && count($requests) === self::BATCH_MAX) {
                        $this->fbeHelper->log(
                            sprintf('Pushing batch %d with %d products', $currentBatch, count($requests))
                        );
                        $response = $this->graphApiAdapter->catalogBatchRequest($catalogId, $requests);
                        $this->fbeHelper->log('Product push response ' . json_encode($response));
                        $responses[] = $response;
                        unset($requests);
                        $currentBatch++;
                    }
                }
            } while (true);
        }

        if (!empty($requests)) {
            $this->fbeHelper->log(sprintf('Pushing batch %d with %d products', $currentBatch, count($requests)));
            $response = $this->graphApiAdapter->catalogBatchRequest($catalogId, $requests);
            $this->fbeHelper->log('Product push response ' . json_encode($response));
            $responses[] = $response;
        }

        return $responses;
    }
}
