<?php
declare(strict_types=1);

namespace Meta\Promotions\Test\Unit\Controller\Adminhtml\Ajax;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Promotions\Model\Promotion\Feed\Uploader;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Promotions\Controller\Adminhtml\Ajax\PromotionFeedUpload;
use Magento\Store\Api\Data\StoreInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;

class PromotionFeedUploadTest extends TestCase
{
    private $fbeHelperMock;
    private $systemConfigMock;
    private $uploaderMock;
    private $contextMock;
    private $resultJsonMock;
    private $storeMock;
    private $storeManagerMock;
    private $requestMock;
    private $object;
    private $subject;

    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->uploaderMock = $this->getMockBuilder(Uploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);

        $this->object = new ObjectManager($this);
        $this->subject = $this->object->getObject(PromotionFeedUpload::class, [
            'context' => $this->contextMock,
            'resultJsonFactory' => $this->resultJsonMock,
            'fbeHelper' => $this->fbeHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'uploader' => $this->uploaderMock
        ]);
    }

    public function testExecuteForJsonNoStoreParam()
    {
        $storeId = 1;
        $storeName = 'Default Store';

        $this->fbeHelperMock->expects($this->exactly(2))
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->storeMock->expects($this->once())
            ->method('getName')
            ->willReturn($storeName);

        $this->subject->executeForJson();
    }

    public function testExecuteForJsonStoreParam()
    {
        $storeId = 1;
        $storeName = 'Default Store';

        $this->fbeHelperMock->expects($this->exactly(2))
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->storeMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturn($storeName);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($storeId);
        $this->systemConfigMock->expects($this->once())
            ->method('getStoreManager')
            ->willReturn($this->storeManagerMock);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->storeMock);

        $this->subject->executeForJson();
    }

    public function testExecuteForJsonWithAccessToken()
    {
        $storeId = 1;
        $storeName = 'Default Store';
        $accessToken = 'akjfbkhab-afjhavfj-ahfhvgja';
        $pushFeedResponse = ['Success' => true];

        $this->fbeHelperMock->expects($this->exactly(2))
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->storeMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturn($storeName);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($storeId);
        $this->systemConfigMock->expects($this->once())
            ->method('getStoreManager')
            ->willReturn($this->storeManagerMock);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->storeMock);

        $this->uploaderMock->expects($this->once())
            ->method('uploadPromotions')
            ->with($storeId)
            ->willReturn($pushFeedResponse);
        $this->systemConfigMock->expects($this->once())
            ->method('getAccessToken')
            ->with($storeId)
            ->willReturn($accessToken);

        $this->subject->executeForJson();
    }
}
