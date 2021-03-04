<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Pixel;

use Facebook\BusinessExtension\Helper\EventIdGenerator;
use Facebook\BusinessExtension\Helper\FBEHelper;

class ProductInfoForAddToCart extends \Magento\Framework\App\Action\Action
{

    protected $_resultJsonFactory;
    protected $_productFactory;
    protected $_fbeHelper;
    protected $_eventManager;
    protected $_formKeyValidator;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        FBEHelper $helper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_productFactory = $productFactory;
        $this->_fbeHelper = $helper;
        $this->_eventManager = $eventManager;
        $this->_formKeyValidator = $formKeyValidator;
    }

    private function getCategory($product)
    {
        $category_ids = $product->getCategoryIds();
        if (count($category_ids) > 0) {
            $category_names = [];
            $category_model = $this->_fbeHelper->getObject(\Magento\Catalog\Model\Category::class);
            foreach ($category_ids as $category_id) {
                $category = $category_model->load($category_id);
                $category_names[] = $category->getName();
            }
            return addslashes(implode(',', $category_names));
        } else {
            return null;
        }
    }

    private function getValue($product)
    {
        if ($product && $product->getId()) {
            $price = $product->getFinalPrice();
            $price_helper = $this->_fbeHelper->getObject(\Magento\Framework\Pricing\Helper\Data::class);
            return $price_helper->currency($price, false, false);
        } else {
            return null;
        }
    }

    private function getProductInfo($product_sku)
    {
        $response_data = [];
        $product = $this->_productFactory->create();
        $product->load($product->getIdBySku($product_sku));
        if ($product->getId()) {
            $response_data['id'] = $product->getId();
            $response_data['name'] = $product->getName();
            $response_data['category'] = $this->getCategory($product);
            $response_data['value'] = $this->getValue($product);
        }
        return $response_data;
    }

    public function execute()
    {
        $product_sku = $this->getRequest()->getParam('product_sku', null);
        if ($this->_formKeyValidator->validate($this->getRequest()) && $product_sku) {
            $response_data = $this->getProductInfo($product_sku);
          // If the sku is valid
          // The event id is added in the response
          // And a CAPI event is created
            if (count($response_data) > 0) {
                $event_id = EventIdGenerator::guidv4();
                $response_data['event_id'] = $event_id;
                $this->trackServerEvent($event_id);
                $result = $this->_resultJsonFactory->create();
                $result->setData(array_filter($response_data));
                return $result;
            }
        } else {
            $this->_redirect('noroute');
        }
    }

    public function trackServerEvent($eventId)
    {
        $this->_eventManager->dispatch(
            'facebook_businessextension_ssapi_add_to_cart',
            ['eventId' => $eventId]
        );
    }
}
