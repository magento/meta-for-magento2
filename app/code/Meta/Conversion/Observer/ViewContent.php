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
use Meta\BusinessExtension\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Meta\Conversion\Helper\ServerEventFactory;

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
            $contentId = $this->_magentoDataHelper->getContentId($product);
            if ($product && $product->getId()) {
                $customData['value'] = $this->_magentoDataHelper->getValueForProduct($product);
                $customData['content_ids'] = [$contentId];
                $customData['content_category'] = $this->_magentoDataHelper->getCategoriesForProduct($product);
                $customData['content_name'] = $product->getName();
                // https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/custom-data/#contents
                $customData['contents'] = [
                    [
                        'id' => $contentId,
                        'item_price' => $this->_magentoDataHelper->getValueForProduct($product)
                    ]
                ];
                $customData['content_type'] = $this->_magentoDataHelper->getContentType($product);
            }
            $event = ServerEventFactory::createEvent('ViewContent', array_filter($customData), $eventId);
            $this->serverSideHelper->sendEvent($event);
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
        return $this;
    }
}
