<?php
declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Meta\Conversion\Api\TrackerInterface;

class AddToWishlist implements TrackerInterface
{
    private const EVENT_TYPE = "AddToWishlist";

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
    public function getPayload(array $params): array
    {
        return [
            'value' => $params['value'],
            'currency' => $params['currency'],
            'content_ids' => $params['content_ids'],
            'content_category' => $params['content_category'],
            'content_name' => $params['content_name'],
            'contents' => [
                [
                    'product_id' => $params['contents'][0]['id'],
                    'quantity' => $params['contents'][0]['quantity'],
                ]
            ]
        ];
    }
}
