<?php

declare(strict_types=1);

namespace Meta\Conversion\Controller\Pixel;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use FacebookAds\Object\ServerSide\Util;

class Tracker implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

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
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param RequestInterface $request
     * @param FBEHelper $fbeHelper
     * @param JsonFactory $jsonFactory
     * @param PublisherInterface $publisher
     * @param JsonSerializer $jsonSerializer
     * @param array $pixelEvents
     */
    public function __construct(
        RequestInterface $request,
        FBEHelper $fbeHelper,
        JsonFactory $jsonFactory,
        PublisherInterface $publisher,
        JsonSerializer $jsonSerializer,
        array $pixelEvents = []
    ) {
        $this->request = $request;
        $this->fbeHelper = $fbeHelper;
        $this->jsonFactory = $jsonFactory;
        $this->publisher = $publisher;
        $this->jsonSerializer = $jsonSerializer;
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

            if ($eventName) {
                $payload = $this->pixelEvents[$eventName]->getPayload($params);
                $payload['event_id'] = $params['eventId'];
                $payload['event_type'] = $this->pixelEvents[$eventName]->getEventType();
                $payload['request_uri'] = Util::getRequestUri();
                $payload['user_agent'] = Util::getHttpUserAgent();
                $payload['fbp'] = Util::getFbp();
                $payload['fbc'] = Util::getFbc();
                if (isset($payload)) {
                    $this->publisher->publish('send.conversion.event.to.meta', $this->jsonSerializer->serialize($payload));
                    $response['success'] = true;
                }
            }
        } catch (\Exception $e) {
            $response['success'] = false;
            $this->fbeHelper->logException($e);
        }
        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}
