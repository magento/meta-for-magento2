<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;

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
    protected $_fbeHelper;

  /**
   * @var AAMFieldsExtractorHelper
   */
    protected $_aamFieldsExtractorHelper;

   /**
    * Constructor
    * @param FBEHelper $fbeHelper
    * @param AAMFieldsExtractorHelper $aamFieldsExtractorHelper
    */
    public function __construct(
        FBEHelper $fbeHelper,
        AAMFieldsExtractorHelper $aamFieldsExtractorHelper
    ) {
        $this->_fbeHelper = $fbeHelper;
        $this->_aamFieldsExtractorHelper = $aamFieldsExtractorHelper;
    }

    public function sendEvent($event, $userDataArray = null)
    {
        try {
            $api = Api::init(null, null, $this->_fbeHelper->getAccessToken());

            $event = $this->_aamFieldsExtractorHelper->setUserData($event, $userDataArray);

            $this->trackedEvents[] = $event;

            $events = [];
            array_push($events, $event);

            $request = (new EventRequestAsync($this->_fbeHelper->getPixelID()))
            ->setEvents($events)
            ->setPartnerAgent($this->_fbeHelper->getPartnerAgent());

            $this->_fbeHelper->log('Sending event '.$event->getEventId());

            $request->execute()
            ->then(
                null,
                function (RequestException $e) {
                    $this->_fbeHelper->log("RequestException: ".$e->getMessage());
                }
            );
        } catch (Exception $e) {
            $this->_fbeHelper->log(json_encode($e));
        }
    }

    public function getTrackedEvents()
    {
        return $this->trackedEvents;
    }
}
