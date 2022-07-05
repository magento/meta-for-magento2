<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml\Product\Form;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Helper\Product\Identifier as ProductIdentifier;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Text;

class Diagnostics extends Text
{
    protected $storeId;

    /**
     * @var ProductInterface
     */
    protected $product;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var ProductIdentifier
     */
    protected $productIdentifier;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @param GraphAPIAdapter $graphApiAdapter
     * @param ProductIdentifier $productIdentifier
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        GraphAPIAdapter $graphApiAdapter,
        ProductIdentifier $productIdentifier,
        SystemConfig $systemConfig,
        FBEHelper $fbeHelper,
        Context $context,
        array $data = []
    ) {
        $this->graphApiAdapter = $graphApiAdapter;
        $this->productIdentifier = $productIdentifier;
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
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
     * @param ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getFacebookProductDiagnostics()
    {
        try {
            $retailerId = $this->productIdentifier->getMagentoProductRetailerId($this->product);
            $catalogId = $this->systemConfig->getCatalogId($this->storeId);
            $product = $this->graphApiAdapter->getProductByRetailerId($catalogId, $retailerId);
            $fbProductId = $product['data'][0]['id'] ?? false;
            if ($fbProductId) {
                return $this->graphApiAdapter->getProductErrors($fbProductId);
            }
        } catch (\Exception $e) {
            $this->fbeHelper->logCritical($e->getMessage());
        }
        return [];
    }

    /**
     * Render html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $diagnosticsReport = $this->getFacebookProductDiagnostics()['errors'] ?? [];
        if (empty($diagnosticsReport)) {
            return '';
        }

        $diagnosticsHtml = '<p style="font-weight: bold;">Facebook diagnostic report:</p><ul>';
        foreach ($diagnosticsReport as $errorItem) {
            $diagnosticsHtml .= '<li class="message message-warning list-item" style="list-style-type: none;">' .
                $this->_escaper->escapeHtml($errorItem['title']) . ': ' .
                $this->_escaper->escapeHtml($errorItem['description']) .
            '</li>';
        }
        $diagnosticsHtml .= '</ul>';
        return '<div class="admin__fieldset" style="padding-top: 0;"><div class="admin__field">
<div class="admin__field-label"></div>
<div class="admin__field-control">' . $diagnosticsHtml . '</div>
</div></div>';
    }
}
