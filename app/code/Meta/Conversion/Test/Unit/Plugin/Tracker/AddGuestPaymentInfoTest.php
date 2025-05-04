<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Plugin\Tracker;

use Magento\Quote\Api\Data\CartInterface;
use Meta\Conversion\Plugin\Tracker\AddPaymentInfo;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Plugin\Tracker\AddGuestPaymentInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Meta\Conversion\Model\Tracker\AddPaymentInfo as AddPaymentInfoTracker;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Model\CapiTracker;
use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdMask;

class AddGuestPaymentInfoTest extends TestCase
{
    private $addPaymentInfoTrackerMock;
    private $cartRepositoryMock;
    private $magentoDataHelperMock;
    private $capiTrackerMock;
    private $quoteIdMaskFactoryMock;
    private $subject;

    public function setUp(): void
    {
        $this->addPaymentInfoTrackerMock = $this->getMockBuilder(AddPaymentInfoTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiTrackerMock = $this->getMockBuilder(CapiTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteIdMaskFactoryMock = $this->getMockBuilder(QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(AddGuestPaymentInfo::class,[
            'addPaymentInfoTracker' => $this->addPaymentInfoTrackerMock,
            'cartRepository' => $this->cartRepositoryMock,
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'capiTracker' => $this->capiTrackerMock,
            'quoteIdMaskFactory' => $this->quoteIdMaskFactoryMock,
        ]);
    }

    public function testAfterSavePaymentInformation()
    {
        $cartIdHash = 'fkbkaf-12112bj';
        $cartId = 1;
        $cartPayload = [
            'cart_id' => $cartId,
            'items' => [
                'item1', 'item2'
            ],
            'value' => 10,
            'currency' => 'USD'
        ];
        $initialPayload = [
            'cart_id' => $cartId,
            'items' => [
                'item1', 'item2'
            ],
            'value' => 10,
            'currency' => 'USD',
            'content_type' => 'product'
        ];
        $eventType = 'addPaymentInfo';

        $guestPaymentInformationManagementMock = $this->getMockBuilder(GuestPaymentInformationManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addressMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteIdMask = $this->getMockBuilder(QuoteIdMask::class)
            ->addMethods(['getQuoteId'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteIdMaskFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteIdMask);
        $quoteIdMask->expects($this->once())
            ->method('load')
            ->with($cartIdHash, 'masked_id')
            ->willReturnSelf();
        $quoteIdMask->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($cartId);
        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($cartMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCartPayload')
            ->with($cartMock)
            ->willReturn($cartPayload);
        $this->addPaymentInfoTrackerMock->expects($this->once())
            ->method('getPayload')
            ->with($initialPayload)
            ->willReturn($initialPayload);
        $this->addPaymentInfoTrackerMock->expects($this->once())
            ->method('getEventType')
            ->willReturn($eventType);
        $this->capiTrackerMock->expects($this->once())
            ->method('execute')
            ->with($initialPayload, AddPaymentInfo::EVENT_NAME, $eventType, true);

        $this->subject->afterSavePaymentInformation($guestPaymentInformationManagementMock, true, $cartIdHash, 'abc@example.com', $paymentMock, $addressMock);
    }
}
