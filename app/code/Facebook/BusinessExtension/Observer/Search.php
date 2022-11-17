<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
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
    protected $fbeHelper;

    /**
     * @var ServerSideHelper
     */
    protected $serverSideHelper;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Search constructor
     *
     * @param FBEHelper $fbeHelper
     * @param ServerSideHelper $serverSideHelper
     * @param RequestInterface $request
     */
    public function __construct(
        FBEHelper $fbeHelper,
        ServerSideHelper $serverSideHelper,
        RequestInterface $request
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->request = $request;
        $this->serverSideHelper = $serverSideHelper;
    }

    /**
     * @return string
     */
    public function getSearchQuery()
    {
        return htmlspecialchars(
            $this->request->getParam('q'),
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
            $this->serverSideHelper->sendEvent($event);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
