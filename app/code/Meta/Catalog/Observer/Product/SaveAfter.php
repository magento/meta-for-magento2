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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Exception\NoSuchEntityException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Method\BatchApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class SaveAfter implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var BatchApi
     */
    private $batchApi;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepo;

    /**
     * @param SystemConfig $systemConfig
     * @param FBEHelper $helper
     * @param BatchApi $batchApi
     * @param GraphAPIAdapter $graphApiAdapter
     * @param ManagerInterface $messageManager
     * @param ProductRepositoryInterface $productRepo
     */
    public function __construct(
        SystemConfig $systemConfig,
        FBEHelper $helper,
        BatchApi $batchApi,
        GraphAPIAdapter $graphApiAdapter,
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepo
    ) {
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $helper;
        $this->batchApi = $batchApi;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->messageManager = $messageManager;
        $this->productRepo = $productRepo;
    }

    /**
     * Execute observer for product save API call
     *
     * Call an API to product save from facebook catalog
     * after save product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $pid = $product->getId();

        if (!$pid) {
            return;
        }

        $stores = $this->systemConfig->getStoreManager()->getStores(false, true);

        foreach ($stores as $store) {
            $this->updateProduct($store->getId(), $pid);
        }
    }

    /**
     * Process product update
     *
     * @param int $storeId
     * @param int $productId
     * @return void
     */
    private function updateProduct($storeId, $productId): void
    {
        $isActive = $this->systemConfig->isActiveExtension($storeId);
        $shouldIncrement = $this->systemConfig->isActiveIncrementalProductUpdates($storeId);

        if (!($isActive && $shouldIncrement)) {
            return;
        }

        try {
            $product = $this->productRepo->getById($productId, false, $storeId, true);

            if (!$product->getSendToFacebook()) {
                return;
            }

            // @todo implement error handling/logging for invalid access token and other non-happy path scenarios
            // @todo implement batch API status check
            // @todo implement async call

            $catalogId = $this->systemConfig->getCatalogId($storeId);
            $requestData = $this->batchApi->buildRequestForIndividualProduct($product);
            $this->graphApiAdapter->catalogBatchRequest($catalogId, [$requestData]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(
                'Failed to update Meta for one or more stores. Please see Exception log for more detail.'
            );
            $this->fbeHelper->logException($e);
            return;
        } catch (GuzzleException $e) {
            $this->messageManager->addNoticeMessage(
                'Error sending Increment Update To Meta. Please check your Store Connection Settings.'
            );
            $this->fbeHelper->logException($e);
            return;
        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
            return;
        }
    }
}
