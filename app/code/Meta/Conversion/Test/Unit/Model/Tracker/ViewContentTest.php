<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\ViewContent;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Escaper;

class ViewContentTest extends TestCase
{
    private $magentoDataHelperMock;
    private $productRepositoryMock;
    private $productMock;
    private $escaperMock;
    private $subject;

    public function setUp(): void
    {
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(ViewContent::class, [
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'productRepository' => $this->productRepositoryMock,
            'escaper' => $this->escaperMock
        ]);
    }

    public function testGetEventType()
    {
        $this->assertEquals('ViewContent', $this->subject->getEventType());
    }

    public function testGetPayload()
    {
        $productId = 1;
        $productName = 'test-name';
        $param = [
            'productId' => $productId
        ];
        $currency = 'USD';
        $price = 10.00;
        $categoryName = 'test-category';
        $contentType = 'Product';
        $payload = [
            'currency' => $currency,
            'value' => $price,
            'content_ids' => [$productId],
            'content_category' => $categoryName,
            'content_name' => $productName,
            'contents' => [
                [
                    'product_id' => $productId,
                    'quantity' => 1,
                    'item_price' => $price,
                ]
            ],
            'content_type' => $contentType
        ];

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($this->productMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getContentId')
            ->with($this->productMock)
            ->willReturn($productId);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);
        $this->magentoDataHelperMock->expects($this->exactly(2))
            ->method('getValueForProduct')
            ->with($this->productMock)
            ->willReturn($price);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCategoriesForProduct')
            ->with($this->productMock)
            ->willReturn($categoryName);
        $this->productMock->expects($this->once())
            ->method('getName')
            ->willReturn($productName);
        $this->escaperMock->expects($this->once())
            ->method('escapeUrl')
            ->with($productName)
            ->willReturn($productName);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getContentType')
            ->with($this->productMock)
            ->willReturn($contentType);

        $this->assertEquals($payload, $this->subject->getPayload($param));
    }

    public function testGetPayloadException()
    {
        $productId = 1;
        $param = [
            'productId' => $productId
        ];
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException(new NoSuchEntityException(__('Product not found')));
        $this->subject->getPayload($param);
    }
}
