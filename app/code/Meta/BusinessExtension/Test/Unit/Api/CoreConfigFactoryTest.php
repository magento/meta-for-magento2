<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use Meta\BusinessExtension\Model\Api\CoreConfigFactory;
use Meta\BusinessExtension\Model\Api\CoreConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\ObjectManagerInterface;

class CoreConfigFactoryTest extends TestCase
{
    /**
     * @var CoreConfigFactory
     */
    private $metaCoreConfigFactoryObj;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockObjectManagerInterfaceDependency;

    /**
     * Class setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockObjectManagerInterfaceDependency = $this->createMock(ObjectManagerInterface::class);
        $this->metaCoreConfigFactoryObj = new CoreConfigFactory($this->mockObjectManagerInterfaceDependency);
    }

    /**
     * Test create method
     * 
     * @return void
     */
    public function testCreate(): void
    {
        $expectedCoreConfig = $this->createMock(CoreConfig::class);
        $data = $this->getData();

        $this->mockObjectManagerInterfaceDependency->expects($this->once())
            ->method('create')
            ->with(CoreConfig::class, $data)
            ->willReturn($expectedCoreConfig);

        $actualCoreConfig = $this->metaCoreConfigFactoryObj->create($data);

        $this->assertInstanceOf(CoreConfig::class, $actualCoreConfig);
        $this->assertSame($expectedCoreConfig, $actualCoreConfig);
    }

    /**
     * Get data for testing
     * 
     * @return array
     */
    private function getData()
    {
        return [
            'externalBusinessId' => '1234567890',
            'isOrderSyncEnabled' => true,
            'isCatalogSyncEnabled' => true,
            'isPromotionsSyncEnabled' => true,
            'isActiveExtension' => true,
            'productIdentifierAttr' => 'sku',
            'outOfStockThreshold' => '10',
            'feedId' => 'meta_feed_id',
            'installedMetaExtensionVersion' => 'v1.0',
            'graphApiVersion' => 'v21.0',
            'magentoVersion' => 'v2.4.8',
        ];
    }
}
