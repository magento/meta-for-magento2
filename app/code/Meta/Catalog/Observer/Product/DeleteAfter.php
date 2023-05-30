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
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\Data\ProductInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\Catalog\Helper\Product\Identifier;
use Magento\Framework\Message\ManagerInterface;

class DeleteAfter implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     * @param Identifier $identifier
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphApiAdapter $graphApiAdapter,
        FBEHelper $fbeHelper,
        Identifier $identifier,
        ManagerInterface $messageManager
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->identifier = $identifier;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute observer for product delete API call
     *
     * Call an API to product delete from facebook catalog
     * after delete product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if (!$product->getId()) {
            return;
        }

        $stores = $this->systemConfig->getStoreManager()->getStores();

        foreach ($stores as $store) {
            $this->deleteProduct($store->getId(), $product);
        }
    }

    /**
     * Process Product Delete from Meta Catalog
     *
     * @param int $storeId
     * @param ProductInterface $product
     * @return void
     */
    private function deleteProduct($storeId, $product): void
    {
        $isActive = $this->systemConfig->isActiveExtension($storeId);
        $shouldIncrement = $this->systemConfig->isActiveIncrementalProductUpdates($storeId);

        if (!($isActive && $shouldIncrement)) {
            return;
        }

        try {
            // @todo observer should not know how to assemble request
            $requestData = [
                'method' => 'DELETE',
                'data' => ['id' => $this->identifier->getMagentoProductRetailerId($product)],
            ];

            $catalogId = $this->systemConfig->getCatalogId($storeId);
            $this->graphApiAdapter->catalogBatchRequest($catalogId, [$requestData]);
        } catch (GuzzleException $e) {
            $this->messageManager->addErrorMessage(
                'Error deleting product from one or more Meta Catalogs. Please check setup and try again'
            );
            $this->fbeHelper->logException($e);
            return;
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
            return;
        }
    }
}
