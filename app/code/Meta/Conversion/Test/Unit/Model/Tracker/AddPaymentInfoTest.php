<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\AddPaymentInfo;

class AddPaymentInfoTest extends TestCase
{
    private const EVENT_TYPE = "AddPaymentInfo";
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(AddPaymentInfo::class);
    }

    public function testGetEventType()
    {
        $this->assertEquals(self::EVENT_TYPE, $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $params = [
            'contents' => [
                [
                    'id' => 1,
                    'quantity' => 1
                ]
            ],
            'currency' => 'USD',
            'value' => 10,
            'content_type' => 'Product',
            'content_ids' => [1],
            'content_category' => 'test_category'
        ];

        $payload = [
            'currency' => 'USD',
            'value' => 10,
            'content_type' => 'Product',
            'contents' => [
                [
                    'product_id' => 1,
                    'quantity' => 1
                ]
            ],
            'content_ids' => [1],
            'content_category' => 'test_category'
        ];
        $this->assertEquals($payload, $this->subject->getPayload($params));
    }
}
