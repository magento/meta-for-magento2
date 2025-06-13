<?php

namespace Meta\Promotions\Test\Unit\Model\Promotion\Feed\PromotionRetriever;

use PHPUnit\Framework\TestCase;
use Meta\Promotions\Model\Promotion\Feed\PromotionRetriever\PromotionRetriever;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;

class PromotionRetrieverTest extends TestCase
{

    /**
     * @var FBEHelper
     */
    protected $fbeHelperMock;

    /**
     * @var RuleCollection
     */
    protected $ruleCollectionMock;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactoryMock;

    /**
     * @var object
     */
    private $subject;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp():void
    {
        $this->ruleCollectionMock = $this->getMockBuilder(RuleCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleCollectionFactoryMock = $this->getMockBuilder(RuleCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->subject = $this->objectManager->getObject(PromotionRetriever::class, [
            'fbeHelper' => $this->fbeHelperMock,
            'ruleCollection' => $this->ruleCollectionFactoryMock
        ]);

    }

    public function testRetrieve():void
    {
        $websiteId = 1;
        $items = ['item1', 'item2'];

        $this->ruleCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->ruleCollectionMock);
        $this->ruleCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('is_active', 1)
            ->willReturnSelf();
        $this->ruleCollectionMock->expects($this->once())
            ->method('addWebsiteFilter')
            ->with($websiteId)
            ->willReturnSelf();
        $this->ruleCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($items);

        $this->subject->retrieve($websiteId);
    }

    public function testGetLimit():void
    {
        self::assertEquals(2000, $this->subject->getLimit());
    }
}
