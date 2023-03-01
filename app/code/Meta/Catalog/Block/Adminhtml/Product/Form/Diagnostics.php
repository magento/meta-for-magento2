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

namespace Meta\Catalog\Block\Adminhtml\Product\Form;

use GuzzleHttp\Exception\GuzzleException;
use Meta\Catalog\Helper\Product\Identifier as ProductIdentifier;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Text;

class Diagnostics extends Text
{
    /**
     * @var string
     */
    private $storeId;

    /**
     * @var ProductInterface
     */
    private $product;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var ProductIdentifier
     */
    private $productIdentifier;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

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
     * Get store id
     *
     * @param string $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Set product
     *
     * @param ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Get product diagnostics
     *
     * @return array
     * @throws GuzzleException
     */
    private function getFacebookProductDiagnostics()
    {
        try {
            $retailerId = $this->productIdentifier->getMagentoProductRetailerId($this->product);
            $catalogId = $this->systemConfig->getCatalogId($this->storeId);
            $product = $this->graphApiAdapter->getProductByRetailerId($catalogId, $retailerId);
            $fbProductId = $product['data'][0]['id'] ?? false;
            if ($fbProductId) {
                $productErrors = $this->graphApiAdapter->getProductErrors($fbProductId)['errors'] ?? [];
                // remove duplicates
                return array_unique($productErrors, SORT_REGULAR);
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
     * @throws GuzzleException
     */
    protected function _toHtml()
    {
        $diagnosticsReport = $this->getFacebookProductDiagnostics();
        if (empty($diagnosticsReport)) {
            return '';
        }

        $diagnosticsHtml = '<p style="font-weight: bold;">Facebook diagnostic report:</p><ul>';
        foreach ($diagnosticsReport as $errorItem) {
            $diagnosticsHtml .= '<li class="message message-warning list-item" style="list-style-type: none;">' .
                $this->_escaper->escapeHtml($errorItem['title']) . ': ' .
                $this->stripTags($errorItem['description'], '<br>') .
            '</li>';
        }
        $diagnosticsHtml .= '</ul>';
        return '<div class="admin__fieldset" style="padding-top: 0;"><div class="admin__field">
<div class="admin__field-label"></div>
<div class="admin__field-control">' . $diagnosticsHtml . '</div>
</div></div>';
    }
}
