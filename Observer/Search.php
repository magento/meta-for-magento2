<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class Search implements ObserverInterface
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
   * @var RequestInterface
   */
    protected $_request;

    public function __construct(
        FBEHelper $fbeHelper,
        ServerSideHelper $serverSideHelper,
        RequestInterface $request
    ) {
        $this->_fbeHelper = $fbeHelper;
        $this->_request = $request;
        $this->_serverSideHelper = $serverSideHelper;
    }

    public function getSearchQuery()
    {
        return htmlspecialchars(
            $this->_request->getParam('q'),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $customData = [
            'search_string' => $this->getSearchQuery()
            ];
            $event = ServerEventFactory::createEvent('Search', $customData, $eventId);
            $this->_serverSideHelper->sendEvent($event);
        } catch (Exception $e) {
            $this->_fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
