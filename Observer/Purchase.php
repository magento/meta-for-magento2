<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
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
    protected $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    protected $magentoDataHelper;

    /**
     * @var ServerSideHelper
     */
    protected $serverSideHelper;

    /**
     * Purchase constructor
     *
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param ServerSideHelper $serverSideHelper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->serverSideHelper = $serverSideHelper;
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
                'currency'     => $this->magentoDataHelper->getCurrency(),
                'value'        => $this->magentoDataHelper->getOrderTotal(),
                'content_type' => 'product',
                'content_ids'  => $this->magentoDataHelper->getOrderContentIds(),
                'contents'     => $this->magentoDataHelper->getOrderContents(),
                'order_id'     => (string)$this->magentoDataHelper->getOrderId()
            ];
            $event = ServerEventFactory::createEvent('Purchase', array_filter($customData), $eventId);
            $userDataFromOrder = $this->magentoDataHelper->getUserDataFromOrder();
            $this->serverSideHelper->sendEvent($event, $userDataFromOrder);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
