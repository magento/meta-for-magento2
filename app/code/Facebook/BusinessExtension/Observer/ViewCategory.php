<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class ViewCategory implements ObserverInterface
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

    public function __construct(
        FBEHelper $fbeHelper,
        ServerSideHelper $serverSideHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->registry = $registry;
        $this->serverSideHelper = $serverSideHelper;
    }

    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $customData = [];
            $category = $this->registry->registry('current_category');
            if ($category) {
                $customData['content_category'] = addslashes($category->getName());
            }
            $event = ServerEventFactory::createEvent('ViewCategory', $customData, $eventId);
            $this->serverSideHelper->sendEvent($event);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
