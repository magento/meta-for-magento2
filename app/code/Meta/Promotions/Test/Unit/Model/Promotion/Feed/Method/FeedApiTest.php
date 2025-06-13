<?php
declare(strict_types=1);

namespace Meta\Promotions\Test\Unit\Model\Promotion\Feed\Method;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config;
use Meta\Promotions\Model\Promotion\Feed\Builder;
use Meta\Promotions\Model\Promotion\Feed\Method\FeedApi;
use Meta\Promotions\Model\Promotion\Feed\PromotionRetriever\PromotionRetriever;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class FeedApiTest extends TestCase
{
    private $systemConfigMock;
    private $graphApiAdapterMock;
    private $filesystemMock;
    private $builderMock;
    private $loggerMock;
    private $promotionRetrieverMock;
    private $directoryMock;
    private $fileStreamMock;
    private $storeManagerMock;
    private $storeMock;
    private $object;
    private $feedApi;


    public function setUp(): void
    {
        $this->systemConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->graphApiAdapterMock = $this->getMockBuilder(GraphAPIAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->promotionRetrieverMock = $this->getMockBuilder(PromotionRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryMock = $this->getMockBuilder(WriteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fileStreamMock = $this->getMockBuilder(FileWriteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->feedApi = $object->getObject(FeedApi::class, [
            'systemConfig' => $this->systemConfigMock,
            'graphApiAdapter' => $this->graphApiAdapterMock,
            'filesystem' => $this->filesystemMock,
            'builder' => $this->builderMock,
            'logger' => $this->loggerMock,
            'promotionRetriever' => $this->promotionRetrieverMock,
        ]);
    }

    public function testExecute()
    {
        $storeId = 1;
        $storeCode = 'default';
        $filePath = '/var/www/html/var/export/facebook_promotions.tsv';
        $commercePartnerIntegrationId = 'abc123';

        $this->systemConfigMock->method('getStoreManager')->willReturn($this->storeManagerMock);
        $this->storeManagerMock->method('getStore')->with($storeId)->willReturn($this->storeMock);
        $this->storeManagerMock->method('getDefaultStoreView')->willReturn($this->storeMock);
        $this->storeMock->method('getCode')->willReturn($storeCode);
        $this->storeMock->method('getWebsiteId')->willReturn(1);
        $this->storeMock->method('getId')->willReturn($storeId);

        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->willReturn($this->directoryMock);

        $this->directoryMock->expects($this->once())
            ->method('create')
            ->with('export');

        $this->directoryMock->expects($this->once())
            ->method('openFile')
            ->willReturn($this->fileStreamMock);

        $this->fileStreamMock->expects($this->once())->method('lock');
        $this->fileStreamMock->expects($this->exactly(2))->method('writeCsv');
        $this->fileStreamMock->expects($this->once())->method('unlock');

        $this->directoryMock->method('getAbsolutePath')->willReturn($filePath);

        $this->builderMock->method('setStoreId')->with($storeId);
        $this->builderMock->method('getHeaderFields')->willReturn(['Header1', 'Header2']);

        $offerMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadCouponCode'])
            ->getMock();

        $this->promotionRetrieverMock->method('retrieve')->willReturn([$offerMock]);
        $this->builderMock->method('buildPromoEntry')->willReturn(['field1' => 'value1', 'field2' => 'value2']);

        $offerMock->expects($this->once())->method('loadCouponCode');

        $this->systemConfigMock->method('isDebugMode')->with($storeId)->willReturn(false);
        $this->systemConfigMock->method('getAccessToken')->with($storeId)->willReturn('dummy_token');
        $this->systemConfigMock->method('getCommercePartnerIntegrationId')->with($storeId)->willReturn($commercePartnerIntegrationId);

        $this->graphApiAdapterMock->method('setDebugMode')->willReturnSelf();
        $this->graphApiAdapterMock->method('setAccessToken')->willReturnSelf();

        $this->graphApiAdapterMock->expects($this->once())
            ->method('uploadFile')
            ->with(
                $commercePartnerIntegrationId,
                $filePath,
                'PROMOTIONS',
                'create'
            )
            ->willReturn(true);

        $result = $this->feedApi->execute($storeId);
        $this->assertTrue($result);
    }
}
