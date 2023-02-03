<?php

declare(strict_types=1);

namespace Meta\Conversion\Api;

interface TrackerInterface
{
    /**
     * Get event type
     *
     * @return string
     */
    public function getEventType(): string;

    /**
     * Get the payload for event
     *
     * @param array $params
     * @return array
     */
    public function getPayload(array $params): array;
}
