<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\Conversion\Observer;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Escaper;

use Meta\Conversion\Helper\ServerEventFactory;

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
     * @var ServerEventFactory
     */
    private $serverEventFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param ServerSideHelper $serverSideHelper
     * @param ServerEventFactory $serverEventFactory
     * @param RequestInterface $request
     * @param Escaper $escaper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper,
        ServerEventFactory $serverEventFactory,
        RequestInterface $request,
        Escaper $escaper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->serverSideHelper = $serverSideHelper;
        $this->serverEventFactory = $serverEventFactory;
        $this->request = $request;
        $this->escaper = $escaper;
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
            $productId = $this->request->getParam('product_id', null);
            if ($productId) {
                $product = $this->magentoDataHelper->getProductById($productId);
            } else {
                $productSku = $this->request->getParam('product_sku', null);
                $product = $this->magentoDataHelper->getProductBySku($productSku);
            }
            if ($product && $product->getId()) {
                $customData = [
                    'currency'         => $this->magentoDataHelper->getCurrency(),
                    'value'            => $this->magentoDataHelper->getValueForProduct($product),
                    'content_type'     => $this->magentoDataHelper->getContentType($product),
                    'content_ids'      => [$this->magentoDataHelper->getContentId($product)],
                    'content_category' => $this->magentoDataHelper->getCategoriesForProduct($product),
                    'content_name'     => $this->escaper->escapeUrl($product->getName()),
                    'custom_properties' => [
                        'source'           => $this->fbeHelper->getSource(),
                        'pluginVersion'    => $this->fbeHelper->getPluginVersion()
                    ]
                ];
                $event = $this->serverEventFactory->createEvent('AddToCart', $customData, $eventId);
                $this->serverSideHelper->sendEvent($event);
            }
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
