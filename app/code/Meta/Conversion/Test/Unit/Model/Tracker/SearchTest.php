<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\Search;

class SearchTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Search::class);
    }

    public function testGetEventType()
    {
        $this->assertEquals('Search', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $params = [
            'searchQuery' => 'test product'
        ];

        $this->assertEquals(
            ['search_string' => $params['searchQuery']],
            $this->subject->getPayload($params)
        );
    }
}
