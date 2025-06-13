<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use Magento\Store\Model\Store as CoreStore;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Cron\UpdateMBESettings;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\MBEInstalls;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\DataObject;

class UpdateMBESettingsTest extends TestCase
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var UpdateMBESettings
     */
    private $mbeInstalls;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var UpdateMBESettings
     */
    private $updateMBESettingsMockObj;

    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->fbeHelper = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mbeInstalls = $this->getMockBuilder(MBEInstalls::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->updateMBESettingsMockObj = $objectManager->getObject(
            UpdateMBESettings::class,
            [
                'fbeHelper' => $this->fbeHelper,
                'mbeInstalls' => $this->mbeInstalls,
                'collectionFactory' => $this->collectionFactory
            ]
        );
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecute(): void
    {
        $reflection = new \ReflectionClass(UpdateMBESettings::class);
        $configModulesProperty = $reflection->getProperty('collectionFactory');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->updateMBESettingsMockObj, $this->collectionFactory);

        $item1 = new DataObject(['scope_id' => 10]);
        $item2 = new DataObject(['scope_id' => 20]);
        $expectedItems = [$item1, $item2];
        
        $this->collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addValueFilter')
            ->with('1')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('scope_id')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('getItems')
            ->willReturn($expectedItems);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->collection);

        $this->mbeInstalls->expects($this->exactly(count($expectedItems)))
            ->method('updateMBESettings')
            ->withConsecutive([10], [20])
            ->willReturnSelf();

        $this->mbeInstalls->expects($this->exactly(count($expectedItems)))
            ->method('repairCommercePartnerIntegration')
            ->withConsecutive([10], [20])
            ->willReturn(true);
            
        $this->updateMBESettingsMockObj->execute();
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $reflection = new \ReflectionClass(UpdateMBESettings::class);
        $configModulesProperty = $reflection->getProperty('collectionFactory');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->updateMBESettingsMockObj, $this->collectionFactory);

        $item1 = new DataObject(['scope_id' => 10]);
        $item2 = new DataObject(['scope_id' => 20]);
        $expectedItems = [$item1, $item2];
        
        $this->collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addValueFilter')
            ->with('1')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('scope_id')
            ->willReturnSelf();
        
        $exception = new \Exception('No item found');
        $this->collection->expects($this->once())
            ->method('getItems')
            ->willThrowException($exception);
        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->collection);

        $storeMock = $this->createMock(CoreStore::class);
        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(2);
        $storeMock->method('getFrontendName')->willReturn('Website 2');
        $storeMock->method('getId')->willReturn(2);
        $storeMock->method('getCode')->willReturn('default');

        $this->fbeHelper->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->fbeHelper->expects($this->once())
            ->method('logExceptionImmediatelyToMeta')
            ->with($exception,
                [
                    'store_id' => 2,
                    'event' => 'update_mbe_settings_cron',
                    'event_type' => 'get_mbe_installed_configs'
                ]
            );
        $this->updateMBESettingsMockObj->execute();
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithUpdateMBESettingsException(): void
    {
        $reflection = new \ReflectionClass(UpdateMBESettings::class);
        $configModulesProperty = $reflection->getProperty('collectionFactory');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->updateMBESettingsMockObj, $this->collectionFactory);

        $item1 = new DataObject(['scope_id' => 10]);
        $item2 = new DataObject(['scope_id' => 20]);
        $expectedItems = [$item1, $item2];
        
        $this->collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addValueFilter')
            ->with('1')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('scope_id')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('getItems')
            ->willReturn($expectedItems);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->collection);

        $exception = new \Exception('Unable to update MBE Settings');
        $this->mbeInstalls->expects($this->exactly(count($expectedItems)))
            ->method('updateMBESettings')
            ->withConsecutive([10], [20])
            ->willThrowException($exception);

        $this->fbeHelper->expects($this->exactly(count($expectedItems)))
            ->method('logExceptionImmediatelyToMeta')
            ->withConsecutive([
                $exception,
                    [
                        'store_id' => 10,
                        'event' => 'update_mbe_settings_cron',
                        'event_type' => 'update_mbe_settings'
                    ]
                ],
                [
                    $exception,
                    [
                        'store_id' => 20,
                        'event' => 'update_mbe_settings_cron',
                        'event_type' => 'update_mbe_settings'
                    ]
                ]
            );

        $this->mbeInstalls->expects($this->exactly(count($expectedItems)))
            ->method('repairCommercePartnerIntegration')
            ->withConsecutive([10], [20])
            ->willReturn(true);
            
        $this->updateMBESettingsMockObj->execute();
    }

    /**
     * Test execute function
     * 
     * @return void
     */
    public function testExecuteWithRepairCommercePartnerIntegrationException(): void
    {
        $reflection = new \ReflectionClass(UpdateMBESettings::class);
        $configModulesProperty = $reflection->getProperty('collectionFactory');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->updateMBESettingsMockObj, $this->collectionFactory);

        $item1 = new DataObject(['scope_id' => 10]);
        $item2 = new DataObject(['scope_id' => 20]);
        $expectedItems = [$item1, $item2];
        
        $this->collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addValueFilter')
            ->with('1')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('addFieldToSelect')
            ->with('scope_id')
            ->willReturnSelf();
        $this->collection->expects($this->once())
            ->method('getItems')
            ->willReturn($expectedItems);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->collection);

        $this->mbeInstalls->expects($this->exactly(count($expectedItems)))
            ->method('updateMBESettings')
            ->withConsecutive([10], [20])
            ->willReturnSelf();

        $exception = new \Exception('Unable to update MBE Settings');
        $this->mbeInstalls->expects($this->exactly(count($expectedItems)))
            ->method('repairCommercePartnerIntegration')
            ->withConsecutive([10], [20])
            ->willThrowException($exception);

        $this->fbeHelper->expects($this->exactly(count($expectedItems)))
            ->method('logExceptionImmediatelyToMeta')
            ->withConsecutive([
                $exception,
                    [
                        'store_id' => 10,
                        'event' => 'update_mbe_settings_cron_repair_cpi',
                        'event_type' => 'update_mbe_settings'
                    ]
                ],
                [
                    $exception,
                    [
                        'store_id' => 20,
                        'event' => 'update_mbe_settings_cron_repair_cpi',
                        'event_type' => 'update_mbe_settings'
                    ]
                ]
            );
            
        $this->updateMBESettingsMockObj->execute();
    }
}