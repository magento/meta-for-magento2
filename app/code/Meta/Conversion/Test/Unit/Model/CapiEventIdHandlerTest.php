<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\CapiEventIdHandler;

class CapiEventIdHandlerTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $object = new ObjectManager($this);
        $this->subject = $object->getObject(CapiEventIdHandler::class);
    }

    public function testGetMetaEventId()
    {
        $eventName = 'test_event_name';
        $this->subject->getMetaEventId($eventName);
    }

    public function testSetMetaEventId()
    {
        $eventName = 'test_event_name';
        $eventId = 'test_event_id';
        $this->subject->setMetaEventId($eventName, $eventId);
    }
}
