<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Helper;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Directory\Model\Currency;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class MagentoDataHelperTest extends TestCase
{
    private $storeManagerMock;
    private $productRepositoryMock;
    private $productMock;
    private $categoryRepositoryMock;
    private $categoryMock;
    private $pricingHelperMock;
    private $systemConfigMock;
    private $categoryCollectionFactoryMock;
    private $categoryCollectionMock;
    private $subject;

    public function setUp(): void
    {
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pricingHelperMock = $this->getMockBuilder(PricingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryCollectionFactoryMock = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryCollectionMock = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(MagentoDataHelper::class, [
            'storeManager' => $this->storeManagerMock,
            'productRepository' => $this->productRepositoryMock,
            'categoryRepository' => $this->categoryRepositoryMock,
            'pricingHelper' => $this->pricingHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'categoryCollection' => $this->categoryCollectionFactoryMock,
        ]);
    }

    public function testGetProductBySku()
    {
        $productSku = 'test-sku';
        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->subject->getProductBySku($productSku);
    }

    public function testGetProductBySkuException()
    {
        $productSku = 'invalid-sku';
        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willThrowException(new NoSuchEntityException(__('Product not found')));
        $this->subject->getProductBySku($productSku);
    }

    public function testGetProductById()
    {
        $productId = 1;
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($this->productMock);
        $this->subject->getProductById($productId);
    }

    public function testGetProductByIdException()
    {
        $productId = null;
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException(new NoSuchEntityException(__('Product not found')));
        $this->subject->getProductById($productId);
    }

    public function testGetCategoriesForProduct()
    {
        $categoryIds = [1, 2];
        $categoryNames = ['category1', 'category2'];

        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);
        $this->categoryRepositoryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([1], [2])
            ->willReturn($this->categoryMock, $this->categoryMock);
        $this->categoryMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls(...$categoryNames);

        $this->assertEquals(
            addslashes(implode(',', $categoryNames)),
            $this->subject->getCategoriesForProduct($this->productMock)
        );
    }

    public function testGetCategoriesForProductException()
    {
        $categoryIds = [1, 2];
        $categoryNames = ['category1', 'category2'];

        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);
        $this->categoryRepositoryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([1], [2])
            ->willThrowException(new NoSuchEntityException(__('Category not found')));

        $this->subject->getCategoriesForProduct($this->productMock);
    }

    public function testGetCategoriesForProductBlank()
    {
        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn([]);
        $this->assertEquals('', $this->subject->getCategoriesForProduct($this->productMock));
    }

    public function testGetCategoriesNameById()
    {
        $categoryIds = [1, 2];
        $categoryNames = ['category1', 'category2'];
        $this->categoryCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with('name')
            ->willReturnSelf();
        $this->categoryCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('entity_id', $categoryIds)
            ->willReturn([$this->categoryMock, $this->categoryMock]);
        $this->categoryMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls(...$categoryNames);

        $this->assertEquals(
            addslashes(implode(',', $categoryNames)),
            $this->subject->getCategoriesNameById($categoryIds)
        );
    }

    public function testGetCategoriesNameByIdBlank()
    {
        $this->assertEquals('', $this->subject->getCategoriesNameById([]));
    }

    public function testGetContentType()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');
        $this->assertEquals('product', $this->subject->getContentType($this->productMock));
    }
    public function testGetContentTypeBundle()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(BundleType::TYPE_CODE);
        $this->assertEquals('product_group', $this->subject->getContentType($this->productMock));
    }
    public function testGetContentTypeConfigurable()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(Configurable::TYPE_CODE);
        $this->assertEquals('product_group', $this->subject->getContentType($this->productMock));
    }
    public function testGetContentTypeGrouped()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(GroupedType::TYPE_CODE);
        $this->assertEquals('product_group', $this->subject->getContentType($this->productMock));
    }

    public function testGetContentIdReturnsSku()
    {
        $productSku = 'test-sku';
        $this->setIdentifierAttr('sku'); // assuming 'sku' is value of PRODUCT_IDENTIFIER_SKU
        $this->productMock->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);

        $this->assertEquals($productSku, $this->subject->getContentId($this->productMock));
    }

    public function testGetContentIdReturnsId()
    {
        $productId = 1;
        $this->setIdentifierAttr('id'); // assuming 'id' is value of PRODUCT_IDENTIFIER_ID
        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn($productId);

        $this->assertEquals($productId, $this->subject->getContentId($this->productMock));
    }

    public function testGetContentIdReturnsFalseForInvalidIdentifier()
    {
        $this->setIdentifierAttr('invalid_value');

        $this->assertFalse($this->subject->getContentId($this->productMock));
    }

    private function setIdentifierAttr(string $value): void
    {
        $reflection = new \ReflectionClass($this->subject);
        $property = $reflection->getProperty('identifierAttr');
        $property->setAccessible(true);
        $property->setValue($this->subject, $value);
    }

    public function testGetValueForProduct()
    {
        $price = 10.00;

        $this->productMock->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn($price);
        $this->pricingHelperMock->expects($this->once())
            ->method('currency')
            ->with($price, false, false)
            ->willReturn($price);

        $this->assertEquals($price, $this->subject->getValueForProduct($this->productMock, false, false));
    }

    public function testGetCurrency()
    {
        $currency = 'USD';

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currencyMock = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock->expects($this->once())
            ->method('getCurrentCurrency')
            ->willReturn($currencyMock);
        $currencyMock->expects($this->once())
            ->method('getCode')
            ->willReturn($currency);

        $this->assertEquals($currency, $this->subject->getCurrency($currencyMock));
    }

    public function testGetCartTotal()
    {
        $subtotal = 10.00;
        $cartMock = $this->getMockBuilder(CartInterface::class)
            ->addMethods(['getSubtotal'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $cartMock->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);
        $this->pricingHelperMock->expects($this->once())
            ->method('currency')
            ->with($subtotal, false, false)
            ->willReturn($subtotal);

        $this->assertEquals($subtotal, $this->subject->getCartTotal($cartMock));
    }

    public function testGetCartTotalNull()
    {
        $cartMock = $this->getMockBuilder(CartInterface::class)
            ->addMethods(['getSubtotal'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $cartMock->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(null);

        $this->assertNull($this->subject->getCartTotal($cartMock));
    }

    public function testGetCartNumItems()
    {
        $qty = 1;
        $cartMock = $this->getMockBuilder(CartInterface::class)
            ->addMethods(['getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$quoteItemMock]);
        $quoteItemMock->expects($this->once())
            ->method('getQty')
            ->willReturn($qty);

        $this->assertEquals($qty, $this->subject->getCartNumItems($cartMock));
    }

}
