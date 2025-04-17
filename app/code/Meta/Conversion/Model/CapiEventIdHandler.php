<?php
declare(strict_types=1);

namespace Meta\Conversion\Model;

class CapiEventIdHandler
{
    /**
     * @var array<string, string>
     */
    private array $eventIds = [];

    /**
     * Set an event ID for a specific event name
     *
     * @param string $eventName
     * @param string $eventId
     * @return void
     */
    public function setEventId(string $eventName, string $eventId): void
    {
        $this->eventIds[$eventName] = $eventId;
    }

    /**
     * Get the event ID for a specific event name
     *
     * @param string $eventName
     * @return string|null
     */
    public function getEventId(string $eventName): ?string
    {
        return $this->eventIds[$eventName] ?? null;
    }
}
