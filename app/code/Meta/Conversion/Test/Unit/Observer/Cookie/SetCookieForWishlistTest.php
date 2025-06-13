<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Observer\Cookie;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Observer\Cookie\SetCookieForWishlist;
use Magento\Framework\Escaper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Meta\Conversion\Observer\Common;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Catalog\Model\Product;

class SetCookieForWishlistTest extends TestCase
{
    private $magentoDataHelperMock;
    private $escaperMock;
    private $commonMock;
    private $subject;

    private $observerMock;
    private $wishlistItemMock;
    private $productMock;
    private $eventMock;

    public function setUp(): void
    {
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
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

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(SetCookieForWishlist::class, [
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'escaper' => $this->escaperMock,
            'common' => $this->commonMock
        ]);
    }

    public function testExecute(): void
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
                [
                    'id' => $productSku,
                    'quantity' => 1
                ]
            ],
            'value'            => $productPrice,
            'currency'         => $currency,
        ];

        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getItems')->willReturn([$this->wishlistItemMock]);
        $this->wishlistItemMock->expects($this->once())->method('getProduct')->willReturn($this->productMock);
        $this->magentoDataHelperMock->expects($this->exactly(2))->method('getContentId')->with($this->productMock)->willReturn($productSku);
        $this->productMock->expects($this->once())->method('getName')->willReturn($productName);
        $this->escaperMock->expects($this->once())->method('escapeUrl')->with($productName)->willReturn($productName);
        $this->productMock->expects($this->once())->method('getCategoryIds')->willReturn([$categoryId]);
        $this->magentoDataHelperMock->expects($this->once())->method('getCategoriesNameById')->with([$categoryId])->willReturn($categoryName);
        $this->magentoDataHelperMock->expects($this->once())->method('getValueForProduct')->with($this->productMock)->willReturn($productPrice);
        $this->magentoDataHelperMock->expects($this->once())->method('getCurrency')->willReturn($currency);
        $this->commonMock->expects($this->once())
            ->method('setCookieForMetaPixel')
            ->with(SetCookieForWishlist::META_PIXEL_WISHLIST_COOKIE_NAME, $payload);

        $this->subject->execute($this->observerMock);
    }
}
