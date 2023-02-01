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
use Meta\BusinessExtension\Model\System\Config as ConfigProvider;

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
     * @var ConfigProvider
     */
    private $configProvider;


    /**
     * @param RequestInterface $request
     * @param ServerSideHelper $serverSideHelper
     * @param FBEHelper $fbeHelper
     * @param $pixelEvents
     */
    public function __construct(
        RequestInterface $request,
        ServerSideHelper $serverSideHelper,
        FBEHelper $fbeHelper,
        JsonFactory $jsonFactory,
        ConfigProvider $configProvider,
        array $pixelEvents = []
    ){
        $this->request = $request;
        $this->serverSideHelper = $serverSideHelper;
        $this->fbeHelper = $fbeHelper;
        $this->jsonFactory = $jsonFactory;
        $this->configProvider = $configProvider;
        $this->pixelEvents = $pixelEvents;
    }

    public function execute(): Json
    {
        $response = [];

        $pixelId = $this->configProvider->getPixelId();
        if ($pixelId) {
            try {
                $params = $this->request->getParams();
                $eventName = $params['eventName'];
                $eventId = $params['eventId'];

                if ($eventName) {
                    $payload = $this->pixelEvents[$eventName]->getPayload($params);
                    if (count($payload)) {
                        $eventType = $this->pixelEvents[$eventName]->getEventType();

                        $event = ServerEventFactory::createEvent($eventType, array_filter($payload), $eventId);
                        $this->serverSideHelper->sendEvent($event);
                        $response['success'] = true;
                    }
                }
            } catch (\Exception $ex) {
                $response['success'] = false;
                $this->fbeHelper->log(json_encode($ex));
            }
        } else {
            $response['success'] = false;
        }

        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}
