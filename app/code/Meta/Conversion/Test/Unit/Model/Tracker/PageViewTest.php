<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\PageView;

class PageViewTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(PageView::class);
    }

    public function testGetEventType()
    {
        $this->assertEquals('PageView', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $this->assertEquals([], $this->subject->getPayload([]));
    }
}
