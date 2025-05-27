<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Setup\Patch\Data;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\BusinessExtension\Setup\Patch\Data\DeleteLegacyData;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class DeleteLegacyDataTest extends TestCase
{
    private DeleteLegacyData $deleteLegacyData;

    /** @var ModuleDataSetupInterface|MockObject */
    private $moduleDataSetupMock;

    /**
     * Class setUp method
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $objectManager = new ObjectManager($this);
        $this->deleteLegacyData = $objectManager->getObject(
            DeleteLegacyData::class,
            ['moduleDataSetup' => $this->moduleDataSetupMock]
        );
    }

    /**
     * Test getAliases function
     * 
     * @return void
     */
    public function testGetAliases(): void
    {
        $this->assertEquals([], $this->deleteLegacyData->getAliases());
    }

    /**
     * Test getDependencies function
     * 
     * @return void
     */
    public function testGetDependencies(): void
    {
        $this->assertEquals([], DeleteLegacyData::getDependencies());
    }

    /**
     * Test apply function
     * 
     * @return void
     */
    public function testApply(): void
    {
        $adapterInterface = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->moduleDataSetupMock->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($adapterInterface);
        $adapterInterface->expects($this->once())
            ->method('startSetup');
        $adapterInterface->expects($this->once())
            ->method('endSetup');
        $adapterInterface->expects($this->once())
            ->method('dropTable')
            ->with('facebook_business_extension_config')
            ->willReturnSelf();
        $adapterInterface->expects($this->exactly(44))
            ->method('delete')
            ->willReturnSelf();

        $this->moduleDataSetupMock->expects($this->exactly(2))
            ->method('getTable')
            ->withConsecutive(['eav_attribute'], ['eav_attribute_group'])
            ->willReturnOnConsecutiveCalls('eav_attribute', 'eav_attribute_group');

        $this->deleteLegacyData->apply();
    }
}