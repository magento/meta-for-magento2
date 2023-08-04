<?php
declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use Meta\Conversion\Api\TrackerInterface;

class AddPaymentInfo implements TrackerInterface
{
    private const EVENT_TYPE = "AddPaymentInfo";

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
        $contents = [];
        if (!empty($params['contents'])) {
            foreach ($params['contents'] as $content) {
                $contents[] = [
                    'product_id' => $content['id'],
                    'quantity' => $content['quantity']
                ];
            }
        }
        return [
            'currency' => $params['currency'],
            'value' => $params['value'],
            'content_type' => $params['content_type'],
            'contents' => $contents,
            'content_ids' => $params['content_ids'],
            'content_category' => $params['content_category']
        ];
    }
}
