<?php

declare(strict_types=1);

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

namespace Meta\Conversion\Helper;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\ScopeInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequestAsync;
use GuzzleHttp\Exception\RequestException;

/**
 * Helper to fire ServerSide Event.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ServerSideHelper
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var AAMFieldsExtractorHelper
     */
    private AAMFieldsExtractorHelper $aamFieldsExtractorHelper;

    /**
     * @var array
     */
    private array $trackedEvents = [];

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param AAMFieldsExtractorHelper $aamFieldsExtractorHelper
     * @param SystemConfig $systemConfig
     * @param CustomerSession $customerSession
     */
    public function __construct(
        FBEHelper $fbeHelper,
        AAMFieldsExtractorHelper $aamFieldsExtractorHelper,
        SystemConfig $systemConfig,
        CustomerSession $customerSession
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->aamFieldsExtractorHelper = $aamFieldsExtractorHelper;
        $this->systemConfig = $systemConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * Get logged in customer
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customerSession->isLoggedIn() ? $this->customerSession->getCustomer() : null;
    }

    /**
     * Send event
     *
     * @param Event $event
     * @param array $userDataArray
     * @return void
     */
    public function sendEvent($event, $userDataArray = null)
    {
        try {
            Api::init(null, null, $this->systemConfig->getAccessToken());

            $event = $this->aamFieldsExtractorHelper->setUserData($event, $userDataArray, $this->getCustomer());

            $this->trackedEvents[] = $event;

            $events = [];
            array_push($events, $event);

            $request = (new EventRequestAsync($this->systemConfig->getPixelId()))
                ->setEvents($events)
                ->setPartnerAgent($this->fbeHelper->getPartnerAgent(true));

            // Set server test code to the event
            if ($this->systemConfig->isServerTestModeEnabled(null, ScopeInterface::SCOPE_STORES)) {
                $serverTestCode = $this->systemConfig->getServerTestCode(null, ScopeInterface::SCOPE_STORES);
                if ($serverTestCode) {
                    $request->setTestEventCode($serverTestCode);
                    $this->fbeHelper->log('test code '.$serverTestCode.' attached to event request');
                }
            }

            $this->fbeHelper->log('Sending event ' . $event->getEventName() . ' with ID : ' . $event->getEventId());

            $request->execute()
                ->then(
                    null,
                    function (RequestException $e) {
                        $this->fbeHelper->log("RequestException: " . $e->getMessage());
                    }
                );
        } catch (\Exception $e) {
            $this->fbeHelper->log(json_encode($e));
        }
    }

    /**
     * Get tracked events
     *
     * @return array
     */
    public function getTrackedEvents()
    {
        return $this->trackedEvents;
    }
}
