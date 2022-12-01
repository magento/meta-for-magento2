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

class InitiateCheckout implements ObserverInterface
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
     * InitiateCheckout constructor
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
                'value'        => $this->magentoDataHelper->getCartTotal(),
                'content_type' => 'product',
                'content_ids'  => $this->magentoDataHelper->getCartContentIds(),
                'num_items'    => $this->magentoDataHelper->getCartNumItems(),
                'contents'     => $this->magentoDataHelper->getCartContents()
            ];
            $event = ServerEventFactory::createEvent('InitiateCheckout', array_filter($customData), $eventId);
            $this->serverSideHelper->sendEvent($event);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
