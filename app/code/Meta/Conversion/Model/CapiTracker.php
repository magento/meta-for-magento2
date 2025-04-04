<?php
declare(strict_types=1);

namespace Meta\Conversion\Model;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Customer\Model\Session as CustomerSession;

class CapiTracker
{

    public function __construct(
        private readonly FBEHelper $fbeHelper,
        private readonly ServerSideHelper $serverSideHelper,
        private readonly ServerEventFactory $serverEventFactory,
        private readonly CustomerSession $customerSession
    ) { }

    public function execute(array $payload, string $eventName, string $eventType): void
    {
        if (isset($payload)) {
            $payload['custom_properties'] = [];
            $payload['custom_properties']['source'] = $this->fbeHelper->getSource();
            $payload['custom_properties']['pluginVersion'] = $this->fbeHelper->getPluginVersion();
            $eventId = $this->generateEventId($eventName);
            $event = $this->serverEventFactory->createEvent($eventType, array_filter($payload), $eventId);
            if (isset($payload['userDataFromOrder'])) {
                $this->serverSideHelper->sendEvent($event, $payload['userDataFromOrder']);
            } else {
                $this->serverSideHelper->sendEvent($event);
            }
        }
    }

    private function generateEventId($eventName): string
    {
        $data = random_bytes(16);

        // Set the version to 4 (UUID version 4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set the variant to 10xx (RFC4122)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        $eventId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        $this->saveEventId($eventName, $eventId);
        return $eventId;
    }

    private function saveEventId($eventName, $eventId): void
    {
        $eventData = $this->customerSession->getEventIds() ?? [];
        $eventData[$eventName] = $eventId;
        $this->customerSession->setEventIds($eventData);
    }
}
