<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface;

class Diagnostics extends \Magento\Backend\Block\Template
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
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        Context $context,
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
