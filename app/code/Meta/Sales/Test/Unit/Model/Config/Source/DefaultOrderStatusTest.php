<?php
declare(strict_types=1);

namespace Meta\Sales\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Sales\Model\Config\Source\DefaultOrderStatus;

class DefaultOrderStatusTest extends TestCase
{
    private $object;
    private $subject;
    public function setUp(): void
    {
        $this->object = new ObjectManager($this);
        $this->subject = $this->object->getObject(DefaultOrderStatus::class);
    }

    public function testToOptionArray(): void
    {
        $options = [
            ['value' => DefaultOrderStatus::ORDER_STATUS_PENDING, 'label' => __('Pending')],
            ['value' => DefaultOrderStatus::ORDER_STATUS_PROCESSING, 'label' => __('Processing')],
        ];
        $this->assertEquals($options, $this->subject->toOptionArray());
    }
}
