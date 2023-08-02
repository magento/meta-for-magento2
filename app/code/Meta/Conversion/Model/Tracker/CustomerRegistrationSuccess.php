<?php
declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use Meta\Conversion\Api\TrackerInterface;

class CustomerRegistrationSuccess implements TrackerInterface
{
    private const EVENT_TYPE = "CompleteRegistration";

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
            'content_name' => $params['content_name'],
            'currency' => $params['currency'],
            'value' => $params['value']
        ];
    }
}
