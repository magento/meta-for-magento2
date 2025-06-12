<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\AddToCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Escaper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product;

class AddToCartTest extends TestCase
{
    private $productRepositoryMock;
    private $escaperMock;
    private $magentoDataHelperMock;
    private $subject;

    public function setUp(): void
    {
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->magentoDataHelperMock = $this->getMockBuilder(MagentoDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(AddToCart::class, [
            'magentoDataHelper' => $this->magentoDataHelperMock,
            'productRepository' => $this->productRepositoryMock,
            'escaper' => $this->escaperMock
        ]);
    }

    public function testGetEventType()
    {
        $this->assertEquals('AddToCart', $this->subject->getEventType());
    }

    public function testGetPayloadException()
    {
        $param = [
            'productId' => 1
        ];
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($param['productId'])
            ->willThrowException(new NoSuchEntityException(__('Product Not Found')));

        $this->assertEquals([], $this->subject->getPayload($param));
    }

    public function testGetPayload()
    {
        $param = [
            'productId' => 1
        ];
        $currency = 'USD';
        $value = 10.00;
        $contentId = 1;
        $productName = 'Test Product';
        $contentType = 'Product';
        $payload = [
            'currency' => $currency,
            'value' => $value,
            'content_ids' => [$contentId],
            'content_name' => $productName,
            'contents' => [
                [
                    'product_id' => $contentId,
                    'quantity' => 1
                ]
            ],
            'content_type' => $contentType
        ];

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($param['productId'])
            ->willReturn($productMock);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getContentId')
            ->with($productMock)
            ->willReturn($contentId);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getValueForProduct')
            ->with($productMock)
            ->willReturn($value);
        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn($productName);
        $this->escaperMock->expects($this->once())
            ->method('escapeUrl')
            ->with($productName)
            ->willReturn($productName);
        $this->magentoDataHelperMock->expects($this->once())
            ->method('getContentType')
            ->with($productMock)
            ->willReturn($contentType);


        $this->assertEquals($payload, $this->subject->getPayload($param));
    }
}
