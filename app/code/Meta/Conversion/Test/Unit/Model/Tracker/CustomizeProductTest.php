<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\CustomizeProduct;
use Meta\Conversion\Helper\MagentoDataHelper;

class CustomizeProductTest extends TestCase
{
    private $magentoDataHelperMock;
    private $subject;

    public function setUp(): void
    {
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(CustomizeProduct::class, ['magentoDataHelper' => $this->magentoDataHelperMock]);
    }

    public function testGetEventType()
    {
        $this->assertEquals('CustomizeProduct', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $currency = 'USD';
        $payload = [
            'content_type' => 'product',
            'content_name' => 'test-sku',
            'content_ids' => [123],
            'currency' => $currency,
            'value' => 10.00
        ];

        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->assertEquals($payload, $this->subject->getPayload($payload));
    }
}
