<?php

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Meta\Conversion\Api\TrackerInterface;
use Meta\Conversion\Helper\MagentoDataHelper;

class CustomizeProduct implements TrackerInterface
{
    private const EVENT_TYPE = 'CustomizeProduct';

    /**
     * @var MagentoDataHelper
     */
    private $magentoDataHelper;

    /**
     * @param MagentoDataHelper $magentoDataHelper
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
    }

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
        $payload = [
            'content_type' => $params['content_type'],
            'content_name' => $params['content_name'],
            'content_ids' => $params['content_ids'],
            'currency' => $this->magentoDataHelper->getCurrency()
        ];
        // include value only if it is present in the parameters as this is optional in the payload for CustomizeProduct event
        if (isset($params['value'])) {
            $payload['value'] = $params['value'];
        }
        return $payload;
    }
}
