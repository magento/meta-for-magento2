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

namespace Meta\Catalog\Observer\Product;

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteAfter implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     */
    public function __construct(SystemConfig $systemConfig, GraphApiAdapter $graphApiAdapter, FBEHelper $fbeHelper)
    {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Call an API to product delete from facebook catalog
     * after delete product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveIncrementalProductUpdates())) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product->getId()) {
            return;
        }

        // @todo observer should not know how to assemble request
        $requestData = [
            'method' => 'DELETE',
            'data' => ['id' => $this->fbeHelper->getRetailerId($product)],
        ];

        try {
            $storeId = $product->getStoreId();
            $catalogId = $this->systemConfig->getCatalogId($storeId);
            $this->graphApiAdapter->catalogBatchRequest($catalogId, [$requestData]);
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
        }
    }
}
