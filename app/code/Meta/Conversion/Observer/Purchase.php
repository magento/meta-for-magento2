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
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Meta\Conversion\Helper\ServerEventFactory;

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
                'order_id'     => (string)$this->magentoDataHelper->getOrderId(),
                'custom_properties' => [
                    'source'           => $this->fbeHelper->getSource(),
                    'pluginVersion'    => $this->fbeHelper->getPluginVersion()
                ]
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
