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
