<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\InitiateCheckout;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;

class InitiateCheckoutTest extends TestCase
{
    private $magentoDataHelperMock;
    private $cartRepositoryMock;
    private $cartMock;
    private $collectionFactoryMock;
    private $collectionMock;
    private $quoteItemMock;
    private $productMock;
    private $categoryMock;
    private $subject;

    protected function setUp(): void
    {
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartMock = $this->getMockBuilder(CartInterface::class)
            ->addMethods(['getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['addAttributeToSelect', 'addAttributeToFilter'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(InitiateCheckout::class, [
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'cartRepository' => $this->cartRepositoryMock,
            'categoryCollection' => $this->collectionFactoryMock
        ]);
    }

    public function testGetEventType()
    {
        $this->assertEquals('InitiateCheckout', $this->subject->getEventType());
    }

    public function testGetPayloadException()
    {
        $quoteId = 1;
        $params = [
            'quoteId' => $quoteId
        ];
        $this->cartRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willThrowException(new NoSuchEntityException(__('Category Not Found')));

        $this->assertEquals([], $this->subject->getPayload($params));
    }

    public function testGetPayload()
    {
        $quoteId = 1;
        $params = [
            'quoteId' => $quoteId
        ];
        $productId = 1;
        $itemQty = 1;
        $itemPrice = 10.00;
        $categoryIds = [1];
        $categoryName = 'Test category';
        $currency = 'USD';
        $this->cartRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->exactly(2))
            ->method('getCategoryIds')
            ->willReturn($categoryIds);
        $this->magentoDataHelperMock->expects($this->exactly(2))
            ->method('getContentId')
            ->with($this->productMock)
            ->willReturn($productId);
        $this->quoteItemMock->expects($this->once())
            ->method('getQty')
            ->willReturn($itemQty);
        $this->quoteItemMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($itemPrice);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCartTotal')
            ->with($this->cartMock)
            ->willReturn($itemPrice);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCartNumItems')
            ->with($this->cartMock)
            ->willReturn($itemQty);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('entity_id', ['in' => $categoryIds])
            ->willReturn([$this->categoryMock]);
        $this->categoryMock->expects($this->once())
            ->method('getName')
            ->willReturn($categoryName);
        $this->subject->getPayload($params);
    }
}
