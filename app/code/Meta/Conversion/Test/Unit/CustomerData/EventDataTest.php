<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\CustomerData;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\CustomerData\EventData;
use Magento\Customer\Model\Session as CustomerSession;

class EventDataTest extends TestCase
{
    private $customerSessionMock;
    private $subject;

    public function setUp(): void
    {
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->addMethods(['getMetaEventIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(EventData::class, ['customerSession' => $this->customerSessionMock]);
    }

    public function testGetSectionDataWithData()
    {
        $metaEventIds = [
            'addtocart' => '123423hbv-3243r1hb-1324r4t'
        ];
        $sectionData = [
            'eventIds' => $metaEventIds
        ];

        $this->customerSessionMock->expects($this->once())->method('getMetaEventIds')->willReturn($metaEventIds);
        $this->assertEquals($sectionData, $this->subject->getSectionData());
    }

    public function testGetSectionDataWithoutData()
    {
        $sectionData = [
            'eventIds' => []
        ];

        $this->customerSessionMock->expects($this->once())->method('getMetaEventIds')->willReturn(null);
        $this->assertEquals($sectionData, $this->subject->getSectionData());
    }
}
