<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class Purchase implements ObserverInterface
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
   * Constructor
   * @param \Psr\Log\LoggerInterface $logger
   * @param FBEHelper $helper
   * @param MagentoDataHelper $helper
   */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper
    ) {
        $this->_fbeHelper = $fbeHelper;
        $this->_magentoDataHelper = $magentoDataHelper;
        $this->_serverSideHelper = $serverSideHelper;
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
            $customData = [
            'currency' => $this->_magentoDataHelper->getCurrency(),
            'value' => $this->_magentoDataHelper->getOrderTotal(),
            'content_type' => 'product',
            'content_ids' => $this->_magentoDataHelper->getOrderContentIds(),
            'contents' => $this->_magentoDataHelper->getOrderContents(),
            'order_id' => (string)$this->_magentoDataHelper->getOrderId()
            ];
            $event = ServerEventFactory::createEvent('Purchase', array_filter($customData), $eventId);
            $userDataFromOrder = $this->_magentoDataHelper->getUserDataFromOrder();
            $this->_serverSideHelper->sendEvent($event, $userDataFromOrder);
        } catch (Exception $e) {
            $this->_fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
