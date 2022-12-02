<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Plugin;

use Facebook\BusinessExtension\Helper\ServerSideHelper\Interceptor;
use FacebookAds\Object\ServerSide\Event;

class CAPIEventsModifierPlugin
{
    /**
     * Updates the CAPI event if needed
     *
     * @param Interceptor $subject
     * @param Event $event
     * @param string[] $user_data
     * @return array
     */
    public function beforeSendEvent($subject, $event, $user_data = null)
    {
        /**
         * You can enrich the event depending on your needs
         * For example, if you want to set the data processing options you can do:
         * $event->setDataProcessingOptions(['LDU'])
         *  ->setDataProcessingOptionsCountry(1)
         *  ->setDataProcessingOptionsState(1000);
         * Read more about data processing options in:
         * https://developers.facebook.com/docs/marketing-apis/data-processing-options
         */
        return [$event, $user_data];
    }
}
