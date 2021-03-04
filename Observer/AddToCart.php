<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class AddToCart implements ObserverInterface
{

  /**
   * @var FBEHelper
   */
    protected $_fbeHelper;

  /**
   * @var MagentoDataHelper
   */
    protected $_magentoDataHelper;

  /**
   * @var ServerSideHelper
   */
    protected $_serverSideHelper;

  /**
   * @var RequestInterface
   */
    protected $_request;

  /**
   * Constructor
   * @param FBEHelper $helper
   * @param MagentoDataHelper $helper
   * @param ServerSideHelper $serverSideHelper
   * @param RequestInterface $request
   */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper,
        RequestInterface $request
    ) {
        $this->_fbeHelper = $fbeHelper;
        $this->_magentoDataHelper = $magentoDataHelper;
        $this->_serverSideHelper = $serverSideHelper;
        $this->_request = $request;
    }

  /**
   * Execute action method for the Observer
   *
   * @param Observer $observer
   * @return  $this
   */
    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $productSku = $this->_request->getParam('product_sku', null);
            $product = $this->_magentoDataHelper->getProductWithSku($productSku);
            if ($product->getId()) {
                $customData = [
                'currency' => $this->_magentoDataHelper->getCurrency(),
                'value' => $this->_magentoDataHelper->getValueForProduct($product),
                'content_type' => 'product',
                'content_ids' => [$product->getId()],
                'content_category' => $this->_magentoDataHelper->getCategoriesForProduct($product),
                'content_name' => $product->getName()
                ];
                $event = ServerEventFactory::createEvent('AddToCart', $customData, $eventId);
                $this->_serverSideHelper->sendEvent($event);
            }
        } catch (Exception $e) {
            $this->_fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
