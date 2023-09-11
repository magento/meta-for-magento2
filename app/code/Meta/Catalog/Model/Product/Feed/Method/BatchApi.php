<?php /** @noinspection PhpUndefinedFieldInspection */

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
namespace Meta\Catalog\Model\Product\Feed\Method;

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Builder;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Configurable as ConfigurableProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Simple as SimpleProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Other as OtherProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;

class BatchApi
{
    private const ATTR_METHOD = 'method';
    private const ATTR_UPDATE = 'UPDATE';
    private const ATTR_DELETE = 'DELETE';
    private const ATTR_DATA = 'data';

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductRetrieverInterface[]
     */
    private $productRetrievers;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param FBEHelper $helper
     * @param GraphAPIAdapter $graphApiAdapter
     * @param SystemConfig $systemConfig
     * @param SimpleProductRetriever $simpleProductRetriever
     * @param ConfigurableProductRetriever $configurableProductRetriever
     * @param OtherProductRetriever $otherProductRetriever
     * @param Builder $builder
     */
    public function __construct(
        FBEHelper $helper,
        GraphAPIAdapter $graphApiAdapter,
        SystemConfig $systemConfig,
        SimpleProductRetriever $simpleProductRetriever,
        ConfigurableProductRetriever $configurableProductRetriever,
        OtherProductRetriever $otherProductRetriever,
        Builder $builder
    ) {
        $this->fbeHelper = $helper;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->systemConfig = $systemConfig;
        $this->productRetrievers = [
            $simpleProductRetriever,
            $configurableProductRetriever,
            $otherProductRetriever
        ];
        $this->builder = $builder;
    }

    /**
     * Build product request
     *
     * @param Product $product
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    private function buildProductRequest(Product $product, $method = self::ATTR_UPDATE)
    {
        return [
            self::ATTR_METHOD => $method,
            self::ATTR_DATA => $this->builder->buildProductEntry($product)
        ];
    }

    /**
     * Build request for individual product
     *
     * @param string $productIdentifier
     */
    public function buildDeleteProductRequest(string $productIdentifier): array
    {
        return [
            self::ATTR_METHOD => self::ATTR_DELETE,
            self::ATTR_DATA => ['id' => $productIdentifier]
        ];
    }

    /**
     * Build request for individual product
     *
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
}
