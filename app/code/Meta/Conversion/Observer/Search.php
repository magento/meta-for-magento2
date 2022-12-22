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
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Meta\Conversion\Helper\ServerEventFactory;

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


    /**
     * @return string
     */
    private function getSearchQuery()
    {
        return htmlspecialchars(
            $this->request->getParam('q'),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
