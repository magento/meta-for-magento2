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

use Meta\Conversion\Helper\EventIdGenerator;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Catalog\Model\Product;

class ProductInfoForAddToCart extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Meta\BusinessExtension\Helper\MagentoDataHelper
     */
    protected $magentoDataHelper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * ProductInfoForAddToCart constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param FBEHelper $helper
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Meta\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        FBEHelper $helper,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Meta\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper
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
     * @param Product $product
     * @return string|null
     */
    private function getCategory(Product $product)
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
            return addslashes(implode(',', $categoryNames));
        } else {
            return null;
        }
    }

    /**
     * @param $product
     * @return mixed
     */
    private function getPriceValue($product)
    {
        return $this->priceHelper->currency($product->getFinalPrice(), false, false);
    }

    /**
     * @param $productSku
     * @return array
     */
    private function getProductInfo($productSku)
    {
        /** @var Product $product */
        $product = $this->magentoDataHelper->getProductBySku($productSku);
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

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $productSku = $this->getRequest()->getParam('product_sku', null);
        if ($this->formKeyValidator->validate($this->getRequest()) && $productSku) {
            $responseData = $this->getProductInfo($productSku);
            // If the sku is valid
            // The event id is added in the response
            // And a CAPI event is created
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

    public function trackServerEvent($eventId)
    {
        $this->_eventManager->dispatch(
            'facebook_businessextension_ssapi_add_to_cart',
            ['eventId' => $eventId]
        );
    }
}
