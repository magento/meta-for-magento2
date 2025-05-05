<?php
declare(strict_types=1);

namespace Meta\Promotions\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Promotions\Cron\PromotionSyncCron;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Promotions\Model\Promotion\Feed\Uploader;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Store\Api\Data\StoreInterface;

class PromotionSyncCronTest extends TestCase
{
    private $fbeHelperMock;
    private $systemConfigMock;
    private $uploaderMock;
    private $object;
    private $subject;

    public function setUp(): void
    {
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->uploaderMock = $this->getMockBuilder(Uploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new ObjectManager($this);
        $this->subject = $this->object->getObject(PromotionSyncCron::class, [
            'systemConfig' => $this->systemConfigMock,
            'uploader' => $this->uploaderMock,
            'fbeHelper' => $this->fbeHelperMock
        ]);
    }

    public function testExecute(): void
    {
        $storeId = 1;
        $uploadResponse = ['Success' => true];

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->systemConfigMock->expects($this->once())
            ->method('getAllOnsiteFBEInstalledStores')
            ->willReturn([$storeMock]);

        $storeMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($storeId);
        $this->systemConfigMock->expects($this->once())
            ->method('isPromotionsSyncEnabled')
            ->with($storeId)
            ->willReturn(true);
        $this->uploaderMock->expects($this->once())
            ->method('uploadPromotions')
            ->with($storeId)
            ->willReturn($uploadResponse);

        $this->subject->execute();
    }
}
