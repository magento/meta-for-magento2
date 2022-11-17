<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use FacebookAds\Api;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequestAsync;

use FacebookAds\Exception\Exception;

use GuzzleHttp\Exception\RequestException;

/**
 * Helper to fire ServerSide Event.
 */
class ServerSideHelper
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var AAMFieldsExtractorHelper
     */
    protected $aamFieldsExtractorHelper;

    /**
     * @var array
     */
    protected $trackedEvents = [];

    /**
     * Constructor
     *
     * @param FBEHelper $fbeHelper
     * @param AAMFieldsExtractorHelper $aamFieldsExtractorHelper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        AAMFieldsExtractorHelper $aamFieldsExtractorHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->aamFieldsExtractorHelper = $aamFieldsExtractorHelper;
    }

    /**
     * @param $event
     * @param null $userDataArray
     */
    public function sendEvent($event, $userDataArray = null)
    {
        try {
            $api = Api::init(null, null, $this->fbeHelper->getAccessToken());

            $event = $this->aamFieldsExtractorHelper->setUserData($event, $userDataArray);

            $this->trackedEvents[] = $event;

            $events = [];
            array_push($events, $event);

            $request = (new EventRequestAsync($this->fbeHelper->getPixelID()))
                ->setEvents($events)
                ->setPartnerAgent($this->fbeHelper->getPartnerAgent(true));

            $this->fbeHelper->log('Sending event ' . $event->getEventId());

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
     * @return array
     */
    public function getTrackedEvents()
    {
        return $this->trackedEvents;
    }
}
