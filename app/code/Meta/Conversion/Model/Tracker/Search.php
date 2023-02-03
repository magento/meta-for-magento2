<?php

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Meta\Conversion\Api\TrackerInterface;

class Search implements TrackerInterface
{

    private const EVENT_TYPE = 'Search';

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getPayload($params): array
    {
        return [
            'search_string' => $params['searchQuery']
        ];
    }
}
