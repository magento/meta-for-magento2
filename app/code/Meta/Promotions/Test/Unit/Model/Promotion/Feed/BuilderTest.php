<?php
declare(strict_types=1);

namespace Meta\Promotions\Test\Unit\Model\Promotion\Feed;

use Magento\SalesRule\Model\RuleFactory as RuleFactory;
use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon\Collection as CouponCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Promotions\Model\Promotion\Feed\Builder;

class BuilderTest extends TestCase
{

    private $fbeHelperMock;
    private $systemConfigMock;
    private $ruleCollectionFactoryMock;
    private $ruleFactoryMock;
    private $couponCollectionFactoryMock;

    private $object;

    private $subject;


    public function setUp():void
    {
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigMock = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleCollectionFactoryMock = $this->getMockBuilder(RuleCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactoryMock = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->couponCollectionFactoryMock = $this->getMockBuilder(CouponCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new ObjectManager($this);

        $this->subject = $this->object->getObject(Builder::class, [
            'fbeHelper' => $this->fbeHelperMock,
            'systemConfig' => $this->systemConfigMock,
            'ruleCollection' => $this->ruleCollectionFactoryMock,
            'ruleFactory' => $this->ruleFactoryMock,
            'couponFactory' => $this->couponCollectionFactoryMock
        ]);
    }

    public function testSetStoreId()
    {
        $storeId = 1;
        $this->subject->setStoreId($storeId);
    }

    public function testBuildPromoEntry()
    {
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->addMethods(['getIsActive', 'getStopRulesProcessing', 'getIsAdvanced', 'getApplyToShipping', 'getIsRss', 'getUseAutoGeneration'])
            ->onlyMethods(['getFromDate','getToDate', 'getPrimaryCoupon', 'getStoreLabels', 'getWebsiteIds', 'getCustomerGroupIds'])
            ->disableOriginalConstructor()
            ->getMock();
        $couponCollectionMock = $this->getMockBuilder(CouponCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $couponMock = $this->getMockBuilder(Coupon::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fromDate = '12-12-2024';
        $toDate = '15-12-2024';
        $isActive = 1;
        $stopRulesProcessing = 1;
        $isAdvanced = 1;
        $applyToShipping = 1;
        $isRss = 1;
        $useAutoGeneration = 1;
        $storeLabels = ['Rule 1', 'Rule 2', 'Rule 3'];
        $websiteId = 1;
        $customerGroupId = 1;

        $ruleMock->expects($this->exactly(2))->method('getFromDate')->willReturn($fromDate);
        $ruleMock->expects($this->exactly(2))->method('getToDate')->willReturn($toDate);
        $ruleMock->expects($this->once())->method('getIsActive')->willReturn($isActive);
        $ruleMock->expects($this->once())->method('getStopRulesProcessing')->willReturn($stopRulesProcessing);
        $ruleMock->expects($this->once())->method('getIsAdvanced')->willReturn($isAdvanced);
        $ruleMock->expects($this->once())->method('getApplyToShipping')->willReturn($applyToShipping);
        $ruleMock->expects($this->once())->method('getIsRss')->willReturn($isRss);
        $ruleMock->expects($this->once())->method('getUseAutoGeneration')->willReturn($useAutoGeneration);

        $this->couponCollectionFactoryMock->expects($this->once())->method('create')->willReturn($couponCollectionMock);
        $couponCollectionMock->expects($this->once())->method('addRuleToFilter')->with($ruleMock)->willReturnSelf();
        $couponCollectionMock->expects($this->once())->method('addGeneratedCouponsFilter')->willReturnSelf();
        $couponCollectionMock->expects($this->once())->method('getItems')->willReturn([$couponMock]);
        $ruleMock->expects($this->once())->method('getPrimaryCoupon')->willReturn($couponMock);
        $ruleMock->expects($this->once())->method('getStoreLabels')->willReturn($storeLabels);
        $ruleMock->expects($this->once())->method('getWebsiteIds')->willReturn($websiteId);
        $ruleMock->expects($this->once())->method('getCustomerGroupIds')->willReturn($customerGroupId);
        $this->subject->buildPromoEntry($ruleMock);
    }

    public function testGetHeaderFields()
    {
        $this->subject->getHeaderFields();
    }
}
