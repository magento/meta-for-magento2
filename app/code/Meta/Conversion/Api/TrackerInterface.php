<?php
 
declare(strict_types=1);

namespace Meta\Conversion\Api;

interface TrackerInterface
{
    /**
     * @return string
     */
    public function getEventType(): string;

    /**
     * @param array $params
     * @return array
     */
    public function getPayload(array $params): array;
}
