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
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Block\Template;
use Psr\Log\LoggerInterface;

/**
 * @api
 */
class Diagnostics extends Template
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    protected $storeId;

    protected $catalogId;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var array
     */
    private $exceptions = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param Context $context
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        FBEHelper $fbeHelper,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->storeId = $this->fbeHelper->getStore()->getId();
        $this->catalogId = $this->systemConfig->getCatalogId($this->storeId);
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getReport()
    {
        $report = [];
        try {
            $response = $this->graphApiAdapter->getCatalogDiagnostics($this->catalogId);
            if (isset($response['diagnostics']['data'])) {
                $report = $response['diagnostics']['data'];
            }
        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
            $this->logger->critical($e->getMessage());
        }
        return $report;
    }

    /**
     * @param array $retailerIds
     * @return array
     */
    protected function getProducts(array $retailerIds)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addStoreFilter($this->storeId)
            ->setStoreId($this->storeId);

        $productIdentifierAttr = $this->systemConfig->getProductIdentifierAttr($this->storeId);
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
     * @param array $diagnosticItem
     * @return array
     */
    public function getSampleAffectedItems(array $diagnosticItem)
    {
        if (!array_key_exists('sample_affected_items', $diagnosticItem)) {
            return [];
        }

        try {
            $fbIds = array_map(function ($a) {
                return $a['id'];
            }, $diagnosticItem['sample_affected_items']);

            $fbProducts = $this->graphApiAdapter->getProductsByFacebookProductIds($this->catalogId, $fbIds);
            $retailerIds = array_map(function ($a) {
                return $a['retailer_id'];
            }, $fbProducts['data']);

            return $this->getProducts($retailerIds);
        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
            $this->logger->critical($e->getMessage());
        }

        return [];
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getAdminUrl(ProductInterface $product)
    {
        $params = ['id' => $product->getId()];
        if ($this->getRequest()->getParam('store')) {
            $params['store'] = $this->getRequest()->getParam('store');
        }
        return $this->getUrl('catalog/product/edit', $params);
    }
}
