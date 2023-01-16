<?php

declare(strict_types=1);

namespace Meta\Conversion\Controller\Pixel;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\EventIdGenerator;

class Tracker extends Action implements HttpPostActionInterface
{

    public function __construct(
        public Context $context,
        private ServerSideHelper $serverSideHelper,
        private FBEHelper $fbeHelper,
        private $pixelEvents = []
    ){
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $params = $this->_request->getParams();
            $eventName = $params['eventName'];

            if ($eventName) {

                $payload = $this->pixelEvents[$eventName]->getPayload($params);
                $eventType = $this->pixelEvents[$eventName]->getEventType();
                $eventId = EventIdGenerator::guidv4();

                $event = ServerEventFactory::createEvent($eventType, $payload, $eventId);
                $this->serverSideHelper->sendEvent($event);
            }
        } catch (\Exception $ex) {
            $this->fbeHelper->log(json_encode($ex));
        }
    }
}
