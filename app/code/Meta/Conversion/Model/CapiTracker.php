<?php
declare(strict_types=1);

namespace Meta\Conversion\Model;

use FacebookAds\Object\ServerSide\Util;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Meta\Conversion\Model\Tracker\Purchase;

class CapiTracker
{

    /**
     * @param CustomerSession $customerSession
     * @param CapiEventIdHandler $capiEventIdHandler
     * @param JsonSerializer $jsonSerializer
     * @param PublisherInterface $publisher
     */
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly CapiEventIdHandler $capiEventIdHandler,
        private readonly JsonSerializer $jsonSerializer,
        private readonly PublisherInterface $publisher
    ) { }

    public function execute(array $payload, string $eventName, string $eventType, bool $useSessionForEventIds = false): void
    {
        if (isset($payload)) {
            // Purchase event is triggered twice sometimes, to prevent that check if event id is already stored for current request
            // if it does prevent the message form being added to the message queue.
            if ((Purchase::EVENT_TYPE == $eventType) && $this->capiEventIdHandler->getMetaEventId($eventName)) {
                return;
            }
            $eventId = $this->generateEventId($eventName, $useSessionForEventIds);
            $payload['event_id'] = $eventId;
            $payload['event_type'] = $eventType;
            $payload['request_uri'] = Util::getRequestUri();
            $payload['user_agent'] = Util::getHttpUserAgent();
            $payload['fbp'] = Util::getFbp();
            $payload['fbc'] = Util::getFbc();
            $this->publisher->publish('send.conversion.event.to.meta', $this->jsonSerializer->serialize($payload));
        }
    }

    private function generateEventId($eventName, $useSessionForEventIds): string
    {
        $data = random_bytes(16);

        // Set the version to 4 (UUID version 4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set the variant to 10xx (RFC4122)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        $eventId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        $this->saveEventId($eventName, $eventId, $useSessionForEventIds);
        return $eventId;
    }

    private function saveEventId($eventName, $eventId, $useSessionForEventIds): void
    {
        if ($useSessionForEventIds) {
            $eventData = [];
            $eventData[$eventName] = $eventId;
            $this->customerSession->setMetaEventIds($eventData);
        } else {
            $this->capiEventIdHandler->setMetaEventId($eventName, $eventId);
        }
    }
}
