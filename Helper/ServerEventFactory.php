<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Util;

/**
 * Factory class for generating new ServerSideAPI events with default parameters.
 */
class ServerEventFactory
{
    /**
     * @param $eventName
     * @param null $eventId
     * @return mixed
     */
    public static function newEvent($eventName, $eventId = null)
    {
      // Capture default user-data parameters passed down from the client browser.
        $userData = (new UserData())
                  ->setClientIpAddress(self::getIpAddress())
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
    private static function getIpAddress()
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
        foreach ($HEADERS_TO_SCAN as $header) {
            if (array_key_exists($header, $_SERVER)) {
                $ipList = explode(',', $_SERVER[$header]);
                foreach ($ipList as $ip) {
                    $trimmedIp = trim($ip);
                    if (self::isValidIpAddress($trimmedIp)) {
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
     * @param $ipAddress
     * @return mixed
     */
    private static function isValidIpAddress($ipAddress)
    {
        return filter_var(
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
     * @param $event
     * @param $data
     * @return mixed
     */
    private static function addCustomData($event, $data)
    {
        $custom_data = $event->getCustomData();

        if (!empty($data['currency'])) {
            $custom_data->setCurrency($data['currency']);
        }

        if (!empty($data['value'])) {
            $custom_data->setValue($data['value']);
        }

        if (!empty($data['content_ids'])) {
            $custom_data->setContentIds($data['content_ids']);
        }

        if (!empty($data['content_type'])) {
            $custom_data->setContentType($data['content_type']);
        }

        if (!empty($data['content_name'])) {
            $custom_data->setContentName($data['content_name']);
        }

        if (!empty($data['content_category'])) {
            $custom_data->setContentCategory($data['content_category']);
        }

        if (!empty($data['search_string'])) {
            $custom_data->setSearchString($data['search_string']);
        }

        if (!empty($data['num_items'])) {
            $custom_data->setNumItems($data['num_items']);
        }

        if (!empty($data['contents'])) {
            $contents = [];
            foreach ($data['contents'] as $content) {
                $contents[] = new Content($content);
            }
            $custom_data->setContents($contents);
        }

        if (!empty($data['order_id'])) {
            $custom_data->setOrderId($data['order_id']);
        }

        return $event;
    }

    /**
     * Create a server side event
     *
     * @param $eventName
     * @param $data
     * @param null $eventId
     * @return mixed
     */
    public static function createEvent($eventName, $data, $eventId = null)
    {
        $event = self::newEvent($eventName, $eventId);

        $event = self::addCustomData($event, $data);

        return $event;
    }
}
