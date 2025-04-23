<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Helper\CatalogConfigUpdateHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\BusinessExtension\Helper\FBEHelper;

class CatalogConfigUpdateHelperTest extends TestCase
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->eventManager = $this->createMock(EventManager::class);

        $objectManager = new ObjectManager($this);
        $this->catalogConfigUpdateHelperMockObj = $objectManager->getObject(
            CatalogConfigUpdateHelper::class,
            [
                'fbeHelper' => $this->fbeHelper,
                'systemConfig' => $this->systemConfig,
                'eventManager' => $this->eventManager
            ]
        );
    }

    /**
     * Test updateCatalogConfiguration function
     * 
     * @return void
     */
    public function testUpdateCatalogConfiguration(): void
    {
        $storeId = 99;
        $oldCatalogId = 1;
        $catalogId = '10';
        $pixelId = '234';
        $triggerFullSync = false;
        $commercePartnerIntegrationId = 'meta_id';

        $this->systemConfig->expects($this->once())
            ->method('getCatalogId')
            ->with($this->equalTo($storeId))
            ->willReturn($oldCatalogId);

        $this->systemConfig->expects($this->exactly(3))
            ->method('saveConfig');

        $this->systemConfig->expects($this->once())
            ->method('cleanCache');

        $this->catalogConfigUpdateHelperMockObj->updateCatalogConfiguration(
            $storeId,
            $catalogId,
            $commercePartnerIntegrationId,
            $pixelId,
            $triggerFullSync
        );
    }
}