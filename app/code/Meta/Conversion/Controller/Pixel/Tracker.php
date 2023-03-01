<?php

declare(strict_types=1);

namespace Meta\Conversion\Controller\Pixel;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Meta\Conversion\Helper\ServerSideHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

class Tracker implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ServerSideHelper
     */
    private $serverSideHelper;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var array|mixed
     */
    private $pixelEvents;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ServerEventFactory
     */
    private $serverEventFactory;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param ServerSideHelper $serverSideHelper
     * @param FBEHelper $fbeHelper
     * @param JsonFactory $jsonFactory
     * @param ServerEventFactory $serverEventFactory
     * @param array $pixelEvents
     */
    public function __construct(
        RequestInterface $request,
        ServerSideHelper $serverSideHelper,
        FBEHelper $fbeHelper,
        JsonFactory $jsonFactory,
        ServerEventFactory $serverEventFactory,
        array $pixelEvents = []
    ) {
        $this->request = $request;
        $this->serverSideHelper = $serverSideHelper;
        $this->fbeHelper = $fbeHelper;
        $this->jsonFactory = $jsonFactory;
        $this->serverEventFactory = $serverEventFactory;
        $this->pixelEvents = $pixelEvents;
    }

    /**
     * Send server event
     *
     * @return Json
     */
    public function execute(): Json
    {
        $response = [];
        try {
            $params = $this->request->getParams();
            $eventName = $params['eventName'];
            $eventId = $params['eventId'];

            if ($eventName) {
                $payload = $this->pixelEvents[$eventName]->getPayload($params);
                if (count($payload)) {
                    // Add source and pluginVersion in the payload as custom properties
                    $payload['custom_properties'] = [];
                    $payload['custom_properties']['source'] = $this->fbeHelper->getSource();
                    $payload['custom_properties']['pluginVersion'] = $this->fbeHelper->getPluginVersion();

                    $eventType = $this->pixelEvents[$eventName]->getEventType();

                    $event = $this->serverEventFactory->createEvent($eventType, array_filter($payload), $eventId);
                    $this->serverSideHelper->sendEvent($event);
                    $response['success'] = true;
                }
            }
        } catch (\Exception $ex) {
            $response['success'] = false;
            $this->fbeHelper->log(json_encode($ex));
        }
        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}
