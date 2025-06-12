<?php
declare(strict_types=1);

namespace Meta\Sales\Test\Unit\Model\Order;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Sales\Model\Order\CreateCancellation;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\Sales\Api\Data\FacebookOrderInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\DB\Transaction;
use Psr\Log\LoggerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;

class CreateCancellationTest extends TestCase
{
    private $orderRepositoryMock;
    private $orderMock;
    private $orderItemMock;
    private $facebookOrderFactoryMock;
    private $facebookOrderMock;
    private $transactionFactoryMock;
    private $transactionMock;
    private $fbeHelperMock;
    private $loggerMock;
    private $object;
    private $subject;

    public function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->facebookOrderFactoryMock = $this->getMockBuilder(FacebookOrderInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->facebookOrderMock = $this->getMockBuilder(FacebookOrderInterface::class)
            ->addMethods(['load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->transactionFactoryMock = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->object = new ObjectManager($this);
        $this->subject = $this->object->getObject(CreateCancellation::class, [
            'orderRepository' => $this->orderRepositoryMock,
            'facebookOrderFactory' => $this->facebookOrderFactoryMock,
            'transactionFactory' => $this->transactionFactoryMock,
            'fbeHelper' => $this->fbeHelperMock,
            'logger' => $this->loggerMock
        ]);
    }

    public function testExecuteNoMagentoOrder(): void
    {
        $data = [
            'id' => 1
        ];
        $facebookCancellationData = [
            'items' => [
                'data' => $data
            ],
        ];
        $this->facebookOrderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->facebookOrderMock);
        $this->facebookOrderMock->expects($this->once())
            ->method('load')
            ->with($data['id'], 'facebook_order_id')
            ->willReturnSelf();

        $this->subject->execute($data, $facebookCancellationData);
    }

    public function testExecutePartiallyCancelled(): void
    {
        $magentoOrderId = 1;
        $data = [
            'id' => 1
        ];
        $facebookCancellationData = [
            'items' => [
                'data' => $data
            ],
        ];
        $this->facebookOrderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->facebookOrderMock);
        $this->facebookOrderMock->expects($this->once())
            ->method('load')
            ->with($data['id'], 'facebook_order_id')
            ->willReturnSelf();
        $this->facebookOrderMock->expects($this->once())
            ->method('getMagentoOrderId')
            ->willReturn($magentoOrderId);
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyCanceled')
            ->willReturn(1);

        $this->subject->execute($data, $facebookCancellationData);
    }

    public function testExecute(): void
    {
        $magentoOrderId = 1;
        $data = [
            'id' => 1
        ];
        $facebookCancellationData = [
            'items' => [
                'data' => [
                    'quantity' => 1
                ]
            ],
        ];
        $this->facebookOrderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->facebookOrderMock);
        $this->facebookOrderMock->expects($this->once())
            ->method('load')
            ->with($data['id'], 'facebook_order_id')
            ->willReturnSelf();
        $this->facebookOrderMock->expects($this->once())
            ->method('getMagentoOrderId')
            ->willReturn($magentoOrderId);
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($magentoOrderId)
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyCanceled')
            ->willReturn(0);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(0);
        $this->orderMock->expects($this->once())
            ->method('cancel')
            ->willReturnSelf();
        $this->orderMock->expects($this->once())
            ->method('setStatus')
            ->with(Order::STATE_CANCELED)
            ->willReturn(null);

        $this->subject->execute($data, $facebookCancellationData);
    }
}
