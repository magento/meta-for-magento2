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
    protected $fbeHelper;

    /**
     * @var ServerSideHelper
     */
    protected $serverSideHelper;

    /**
     * \Magento\Framework\Registry
     */
    protected $registry;

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
        $this->fbeHelper = $fbeHelper;
        $this->registry = $registry;
        $this->serverSideHelper = $serverSideHelper;
        $this->_magentoDataHelper = $magentoDataHelper;
    }

    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $customData = [
                'currency' => $this->_magentoDataHelper->getCurrency()
            ];
            $product = $this->registry->registry('current_product');
            if ($product && $product->getId()) {
                $customData['value'] = $this->_magentoDataHelper->getValueForProduct($product);
                $customData['content_ids'] = [$product->getId()];
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
            $this->serverSideHelper->sendEvent($event);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
