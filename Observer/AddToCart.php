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
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param ServerSideHelper $serverSideHelper
     * @param RequestInterface $request
     */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper,
        RequestInterface $request
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->serverSideHelper = $serverSideHelper;
        $this->request = $request;
    }

    /**
     * Execute action method for the Observer
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $eventId = $observer->getData('eventId');
            $productSku = $this->request->getParam('product_sku', null);
            $product = $this->magentoDataHelper->getProductWithSku($productSku);
            if ($product->getId()) {
                $customData = [
                    'currency'         => $this->magentoDataHelper->getCurrency(),
                    'value'            => $this->magentoDataHelper->getValueForProduct($product),
                    'content_type'     => 'product',
                    'content_ids'      => [$product->getId()],
                    'content_category' => $this->magentoDataHelper->getCategoriesForProduct($product),
                    'content_name'     => $product->getName()
                ];
                $event = ServerEventFactory::createEvent('AddToCart', $customData, $eventId);
                $this->serverSideHelper->sendEvent($event);
            }
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
