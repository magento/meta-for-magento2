<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\ViewContact;
class ViewContactTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(ViewContact::class);
    }

    public function testGetEventType()
    {
        $this->assertEquals('Contact', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $params = [
            'content_type' => 'view_contact'
        ];
        $this->assertEquals(
            ['content_type' => $params['content_type']],
            $this->subject->getPayload($params)
        );
    }
}
