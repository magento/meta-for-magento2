<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Observer\Tracker\AddToWishlist;
use Magento\Framework\Escaper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Meta\Conversion\Observer\Common;
use Magento\Catalog\Model\Product;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\AddToWishlist as AddToWishlistTracker;
use Magento\Wishlist\Model\Item as WishlistItem;

class AddToWishlistTest extends TestCase
{
    private $magentoDataHelperMock;
    private $escaperMock;
    private Common $commonMock;
    private CapiTracker $capiTrackerMock;
    private AddToWishlistTracker $addToWishlistTrackerMock;
    private $subject;
    private $observerMock;
    private $wishlistItemMock;
    private $productMock;
    private $eventMock;

    protected function setUp(): void
    {
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->capiTrackerMock = $this->getMockBuilder(CapiTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addToWishlistTrackerMock = $this->getMockBuilder(AddToWishlistTracker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commonMock = $this->getMockBuilder(Common::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wishlistItemMock = $this->getMockBuilder(WishlistItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $object = new ObjectManager($this);
        $this->subject = $object->getObject(AddToWishlist::class, [
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'escaper' => $this->escaperMock,
            'common' => $this->commonMock,
            'capiTracker' => $this->capiTrackerMock,
            'addToWishlistTracker' => $this->addToWishlistTrackerMock,
        ]);
    }

    public function testExecute()
    {
        $productId = 1;
        $productSku = 'test-sku';
        $productName = 'test-name';
        $productPrice = 10.00;
        $categoryId = 1;
        $categoryName = 'test-category';
        $currency = 'EUR';

        $payload = [
            'content_name'     => $productName,
            'content_category' => $categoryName,
            'content_ids'      => [$productSku],
            'contents'         => [
                'id' => $productSku,
                'quantity' => 1
            ],
            'value'            => $productPrice,
            'currency'         => $currency,
        ];

        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getItems')->willReturn([$this->wishlistItemMock]);
        $this->wishlistItemMock->expects($this->once())->method('getProduct')->willReturn($this->productMock);
        $this->magentoDataHelperMock->expects($this->exactly(2))->method('getContentId')->with($this->productMock)->willReturn($productSku);
        $this->productMock->expects($this->once())->method('getName')->willReturn($productName);
        $this->productMock->expects($this->once())->method('getCategoryIds')->willReturn([$categoryId]);
        $this->magentoDataHelperMock->expects($this->once())->method('getCategoriesNameById')->with([$categoryId])->willReturn($categoryName);
        $this->magentoDataHelperMock->expects($this->once())->method('getValueForProduct')->with($this->productMock)->willReturn($productPrice);
        $this->magentoDataHelperMock->expects($this->once())->method('getCurrency')->willReturn($currency);

        $this->subject->execute($this->observerMock);
    }
}
