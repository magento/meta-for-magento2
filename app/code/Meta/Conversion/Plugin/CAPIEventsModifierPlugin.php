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

namespace Meta\Conversion\Plugin;

use Meta\Conversion\Helper\ServerSideHelper;
use FacebookAds\Object\ServerSide\Event;

class CAPIEventsModifierPlugin
{
    /**
     * Updates the CAPI event if needed
     *
     * @param ServerSideHelper $subject
     * @param Event $event
     * @param string[] $user_data
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $subject
     */
    public function beforeSendEvent(ServerSideHelper $subject, $event, $user_data = null)
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
