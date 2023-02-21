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

namespace Meta\Conversion\Controller\Pixel;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Asset\File\NotFoundException;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\EventIdGenerator;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Catalog\Model\Product;

class ProductInfoForAddToCart extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var Validator
     */
    private Validator $formKeyValidator;

    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var Data
     */
    private Data $priceHelper;

    /**
     * ProductInfoForAddToCart constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductFactory $productFactory
     * @param FBEHelper $helper
     * @param Validator $formKeyValidator
     * @param MagentoDataHelper $magentoDataHelper
     * @param Data $priceHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductFactory $productFactory,
        FBEHelper $helper,
        Validator $formKeyValidator,
        MagentoDataHelper $magentoDataHelper,
        Data $priceHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productFactory = $productFactory;
        $this->fbeHelper = $helper;
        $this->formKeyValidator = $formKeyValidator;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get Category
     *
     * @param Product $product
     * @return string|null
     */
    private function getCategory(Product $product): ?string
    {
        $categoryIds = $product->getCategoryIds();
        if (count($categoryIds) > 0) {
            $categoryNames = [];
            $categoryModel = $this->fbeHelper->getObject(\Magento\Catalog\Model\Category::class);
            foreach ($categoryIds as $categoryId) {
                // @todo replace model load in loop with collection use
                $category = $categoryModel->load($categoryId);
                $categoryNames[] = $category->getName();
            }
            return addslashes(implode(',', $categoryNames)); // phpcs: ignore
        } else {
            return null;
        }
    }

    /**
     * Get formatted price
     *
     * @param Product $product
     * @return float|string
     */
    private function getPriceValue($product)
    {
        return $this->priceHelper->currency($product->getFinalPrice(), false, false);
    }

    /**
     * Get Product Info
     *
     * @param mixed $productSku
     * @param mixed $productId
     * @return array
     */
    private function getProductInfo($productSku, $productId = null): array
    {
        if ($productId) {
            /** @var Product $product */
            $product = $this->magentoDataHelper->getProductById($productId);
        } else {
            /** @var Product $product */
            $product = $this->magentoDataHelper->getProductBySku($productSku);
        }
        if ($product && $product->getId()) {
            return [
                'id'       => $this->magentoDataHelper->getContentId($product),
                'name'     => $product->getName(),
                'category' => $this->getCategory($product),
                'value'    => $this->getPriceValue($product),
            ];
        }
        return [];
    }

    /**
     * Execute function
     *
     * @returns ResultInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $productId = $this->getRequest()->getParam('product_id', null);
        $productSku = $this->getRequest()->getParam('product_sku', null);
        if ($this->formKeyValidator->validate($this->getRequest()) && ($productSku || $productId)) {
            $responseData = $this->getProductInfo($productSku, $productId);
            // If the sku is valid, The event id is added in the response And a CAPI event is created
            if (count($responseData) > 0) {
                $eventId = EventIdGenerator::guidv4();
                $responseData['event_id'] = $eventId;
                $this->trackServerEvent($eventId);
                $result->setData(array_filter($responseData));
            }
        } else {
            return $this->_redirect('noroute');
        }
        return $result;
    }

    /**
     * Track the server event
     *
     * @param string $eventId
     * @return void
     */
    public function trackServerEvent($eventId): void
    {
        $this->_eventManager->dispatch(
            'facebook_businessextension_ssapi_add_to_cart',
            ['eventId' => $eventId]
        );
    }
}
