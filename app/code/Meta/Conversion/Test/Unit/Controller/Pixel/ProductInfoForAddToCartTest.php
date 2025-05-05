<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Controller\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Controller\Pixel\ProductInfoForAddToCart;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Data\Form\FormKey\Validator;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class ProductInfoForAddToCartTest extends TestCase
{
    private $jsonFactoryMock;
    private $jsonMock;
    private $fbeHelperMock;
    private $validatorMock;
    private $magentoDataHelperMock;
    private $priceHelperMock;
    private $eventManagerMock;
    private $requestMock;
    private $resultFactoryMock;
    private $resultMock;
    private $subject;

    public function setUp(): void
    {
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorMock = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(ResultInterface::class)
            ->addMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(ProductInfoForAddToCart::class, [
            'jsonFactory' => $this->jsonFactoryMock,
            'fbeHelper' => $this->fbeHelperMock,
            'formKeyValidator' => $this->validatorMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'priceHelper' => $this->priceHelperMock,
            'eventManager' => $this->eventManagerMock,
            'request' => $this->requestMock,
            'resultFactory' => $this->resultFactoryMock,
        ]);
    }

    public function testExecuteNoValidate()
    {
        $productId = 1;
        $productSku = 'test-sku';

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultMock);
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['product_id', null], ['product_sku', null])
            ->willReturnOnConsecutiveCalls($productId, $productSku);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(false);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->once())
            ->method('setUrl')
            ->with('noroute')
            ->willReturnSelf();
        $this->subject->execute();
    }

    public function testExecute()
    {
        $productId = 1;
        $productSku = 'test-sku';

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultMock);
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['product_id', null], ['product_sku', null])
            ->willReturnOnConsecutiveCalls($productId, $productSku);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(false);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->once())
            ->method('setUrl')
            ->with('noroute')
            ->willReturnSelf();
        $this->subject->execute();
    }
}
