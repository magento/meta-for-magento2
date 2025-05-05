<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\AddToWishlist;

class AddToWishlistTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(AddToWishlist::class);
    }

    public function testGetEventType()
    {
        $this->assertEquals('AddToWishlist', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $param = [
            'value' => 10,
            'currency' => 'USD',
            'content_ids' => '123',
            'content_category' => 'category-1',
            'content_name' => 'product-name',
            'contents' => [
                [
                    'id' => 123,
                    'quantity' => 1
                ]
            ]
        ];
        $payload = [
            'value' => 10,
            'currency' => 'USD',
            'content_ids' => '123',
            'content_category' => 'category-1',
            'content_name' => 'product-name',
            'contents' => [
                [
                    'product_id' => 123,
                    'quantity' => 1
                ]
            ]
        ];
        $this->assertEquals($payload, $this->subject->getPayload($param));
    }
}
