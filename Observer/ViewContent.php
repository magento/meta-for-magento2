<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class ViewContent implements ObserverInterface
{
  /**
   * @var FBEHelper
   */
    protected $_fbeHelper;

  /**
   * @var ServerSideHelper
   */
    protected $_serverSideHelper;

  /**
   * \Magento\Framework\Registry
   */
    protected $_registry;

  /**
   * @var MagentoDataHelper
   */
    protected $_magentoDataHelper;

    public function __construct(
        FBEHelper $fbeHelper,
        ServerSideHelper $serverSideHelper,
        MagentoDataHelper $magentoDataHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->_fbeHelper = $fbeHelper;
        $this->_registry = $registry;
        $this->_serverSideHelper = $serverSideHelper;
        $this->_magentoDataHelper = $magentoDataHelper;
    }

    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $customData = [
            'currency' => $this->_magentoDataHelper->getCurrency()
            ];
            $product = $this->_registry->registry('current_product');
            if ($product && $product->getId()) {
                $customData['value'] = $this->_magentoDataHelper->getValueForProduct($product);
                $customData['content_ids'] = [ $product->getId() ];
                $customData['content_category'] = $this->_magentoDataHelper->getCategoriesForProduct($product);
                $customData['content_name'] = $product->getName();
                $customData['contents'] = [
                [
                'product_id' => $product->getId(),
                'item_price' => $this->_magentoDataHelper->getValueForProduct($product)
                ]
                ];
                $customData['content_type'] = ($product->getTypeId() == Configurable::TYPE_CODE) ?
                    'product_group' : 'product';
            }
            $event = ServerEventFactory::createEvent('ViewContent', array_filter($customData), $eventId);
            $this->_serverSideHelper->sendEvent($event);
        } catch (Exception $e) {
            $this->_fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
