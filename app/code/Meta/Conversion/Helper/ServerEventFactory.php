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

namespace Meta\Conversion\Helper;

use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Util;
use Laminas\Http\PhpEnvironment\Request;
use Magento\Framework\App\RequestInterface;

/**
 * Factory class for generating new ServerSideAPI events with default parameters.
 */
class ServerEventFactory
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $httpRequest;

    /**
     * @var array $customDataMapping Mapping of event fields to setters.
     */
    private array $customDataMapping = [];

    /**
     * @param RequestInterface $httpRequest
     * @param array $customDataMapping
     */
    public function __construct(
        RequestInterface $httpRequest,
        array $customDataMapping = []
    ) {
        $this->httpRequest = $httpRequest;
        $this->customDataMapping = $customDataMapping;
    }

    /**
     * Create a new event.
     *
     * @param string $eventName Name of event to create.
     * @param string|null $eventId
     * @return Event
     */
    public function newEvent($eventName, $eventId = null)
    {
        // Capture default user-data parameters passed down from the client browser.
        $userData = (new UserData())
                  ->setClientIpAddress($this->getIpAddress())
                  ->setClientUserAgent(Util::getHttpUserAgent())
                  ->setFbp(Util::getFbp())
                  ->setFbc(Util::getFbc());

        $event = (new Event())
              ->setEventName($eventName)
              ->setEventTime(time())
              ->setEventSourceUrl(Util::getRequestUri())
              ->setActionSource('website')
              ->setUserData($userData)
              ->setCustomData(new CustomData());

        if ($eventId == null) {
            $event->setEventId(EventIdGenerator::guidv4());
        } else {
            $event->setEventId($eventId);
        }

        return $event;
    }

    /**
     * Get the IP address from the $_SERVER variable
     *
     * @return string|null
     */
    private function getIpAddress()
    {
        $HEADERS_TO_SCAN = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
        ];
        $server = $this->httpRequest->getServerValue();
        foreach ($HEADERS_TO_SCAN as $header) {
            if (array_key_exists($header, $server)) {
                $ipList = explode(',', $server[$header] ?? '');
                foreach ($ipList as $ip) {
                    $trimmedIp = trim($ip);
                    if ($this->isValidIpAddress($trimmedIp)) {
                        return $trimmedIp;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Check if the given ip address is valid
     *
     * @param string $ipAddress
     * @return bool
     */
    private function isValidIpAddress($ipAddress)
    {
        return (bool)filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4
                      | FILTER_FLAG_IPV6
                      | FILTER_FLAG_NO_PRIV_RANGE
            | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Fill customData member of $event with array $data
     *
     * @param Event $event
     * @param array $data
     * @return Event
     */
    private function addCustomData($event, $data)
    {
        $custom_data = $event->getCustomData();

        foreach ($this->customDataMapping as $field => $setter) {
            if (!empty($data[$field])) {
                $custom_data->$setter($data[$field]);
            }
        }

        if (!empty($data['contents'])) {
            $contents = [];
            foreach ($data['contents'] as $content) {
                $contents[] = new Content($content);
            }
            $custom_data->setContents($contents);
        }

        if (!empty($data['custom_properties']) && is_array($data['custom_properties'])) {
            $custom_data->setCustomProperties($data['custom_properties']);
        }

        return $event;
    }

    /**
     * Create a server side event
     *
     * @param string $eventName
     * @param array $data
     * @param string|null $eventId
     * @return Event
     */
    public function createEvent($eventName, $data, $eventId = null)
    {
        $event = $this->newEvent($eventName, $eventId);

        return $this->addCustomData($event, $data);
    }
}
