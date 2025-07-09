<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Model\Tracker\ViewCategory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Escaper;

class ViewCategoryTest extends TestCase
{
    private $categoryRepositoryMock;
    private $escaperMock;
    private $subject;

    public function setUp(): void
    {
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(ViewCategory::class, [
            'categoryRepository' => $this->categoryRepositoryMock,
            'escaper' => $this->escaperMock
        ]);
    }

    public function testGetEventType()
    {
        $this->assertEquals('ViewCategory', $this->subject->getEventType());
    }

    public function testGetPayloadException()
    {
        $categoryId = 1;
        $params = [
            'categoryId' => $categoryId
        ];
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willThrowException(new NoSuchEntityException(__('Category not found')));

        $this->assertEquals([], $this->subject->getPayload($params));
    }

    public function testGetPayload()
    {
        $categoryId = 1;
        $categoryName = 'Test Category';
        $params = [
            'categoryId' => $categoryId
        ];

        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);
        $categoryMock->expects($this->once())
            ->method('getName')
            ->willReturn($categoryName);
        $this->escaperMock->expects($this->once())
            ->method('escapeQuote')
            ->with($categoryName)
            ->willReturn($categoryName);

        $this->assertEquals(['content_category' => $categoryName], $this->subject->getPayload($params));
    }
}
