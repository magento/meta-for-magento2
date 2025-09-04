<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Cron\EnabledModuleLogging;
use Magento\Store\Model\Store;

class EnabledModuleLoggingTest extends TestCase
{
    /**
     * @var GraphAPIAdapter
     */
    private $graphAPIAdapter;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    
    /**
     * Class setUp function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->graphAPIAdapter = $this->createMock(GraphAPIAdapter::class);
        $this->moduleList = $this->createMock(ModuleList::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);

        $objectManager = new ObjectManager($this);
        $this->enabledModuleLoggingMockObj = $objectManager->getObject(
            EnabledModuleLogging::class,
            [
                'graphAPIAdapter' => $this->graphAPIAdapter,
                'moduleList' => $this->moduleList,
                'systemConfig' => $this->systemConfig,
                'productMetadata' => $this->productMetadata
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
        $storeId = 999;
        $cmsId = 99;
        $accessToken = 'hj!!@@&&yuy-';
        $moduleVersion = 'v1.4.4';

        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn($storeId);

        $this->systemConfig->expects($this->once())
            ->method('getAllFBEInstalledStores')
            ->willReturn([$storeMock]);

        $this->systemConfig->expects($this->once())
            ->method('getCommerceAccountId')
            ->with($storeId)
            ->willReturn($cmsId);
        
        $this->systemConfig->expects($this->once())
            ->method('getAccessToken')
            ->with($storeId)
            ->willReturn($accessToken);

        $this->systemConfig->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn($moduleVersion);

        $this->productMetadata->expects($this->once())
            ->method('getVersion')
            ->willReturn($moduleVersion);

        $this->moduleList->expects($this->once())
            ->method('getNames')
            ->willReturn(['Meta_Modules']);

        $this->graphAPIAdapter->expects($this->once())
            ->method('persistLogToMeta')
            ->with(
                [
                'event' => 'commerce_plugin_and_extension_logging',
                'event_type' => 'enabled_modules',
                'seller_platform_app_version' => $moduleVersion,
                'extra_data' => [
                    'enabled_modules' => json_encode(['Meta_Modules']),
                    'extension_version' => $moduleVersion,
                    'cms_ids' => json_encode([$cmsId])
                ]
                ]
            )->willReturn('');

        $this->enabledModuleLoggingMockObj->execute();
    }
}
