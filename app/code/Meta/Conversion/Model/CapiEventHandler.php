<?php
declare(strict_types=1);

namespace Meta\Conversion\Model;

use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\Conversion\Helper\ServerSideHelper;

class CapiEventHandler
{

    /**
     * @param ServerSideHelper $serverSideHelper
     * @param FBEHelper $fbeHelper
     * @param ServerEventFactory $serverEventFactory
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        private readonly ServerSideHelper $serverSideHelper,
        private readonly FBEHelper $fbeHelper,
        private readonly ServerEventFactory $serverEventFactory,
        private readonly JsonSerializer $jsonSerializer
    ) { }

    public function process(string $message): void
    {
        try {
            $payload = $this->jsonSerializer->unserialize($message);
            $eventId = $payload['event_id'];
            $eventType = $payload['event_type'];
            // remove values from array
            unset($payload['event_id']);
            unset($payload['event_type']);

            // Add source and pluginVersion in the payload as custom properties
            $payload['custom_properties'] = [];
            $payload['custom_properties']['source'] = $this->fbeHelper->getSource();
            $payload['custom_properties']['pluginVersion'] = $this->fbeHelper->getPluginVersion();

            $event = $this->serverEventFactory->createEvent($eventType, array_filter($payload), $eventId);
            if (isset($payload['userDataFromOrder'])) {
                $this->serverSideHelper->sendEvent($event, $payload['userDataFromOrder']);
            } else {
                $this->serverSideHelper->sendEvent($event);
            }
        } catch (\Exception $e) {
            $this->fbeHelper->logException($e);
        }
    }
}
