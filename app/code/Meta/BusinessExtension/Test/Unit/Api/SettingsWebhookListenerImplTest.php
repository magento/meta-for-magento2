<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use Meta\BusinessExtension\Model\Api\SettingsWebhookListenerImpl;
use Exception;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Api\CoreConfigInterface;
use Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface;
use Meta\BusinessExtension\Api\SettingsWebhookListenerInterface;
use Meta\BusinessExtension\Api\SettingsWebhookRequestInterface;
use Meta\BusinessExtension\Helper\CatalogConfigUpdateHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DataObject;
use Meta\BusinessExtension\Model\Api\CoreConfigFactory;

class SettingsWebhookListenerImplTest extends TestCase
{
    /**
     * @var SettingsWebhookListenerImpl
     */
    private $settingsWebhookListenerImplMockObj;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var CatalogConfigUpdateHelper
     */
    private $catalogConfigUpdateHelper;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var CoreConfigFactory
     */
    private $coreConfigFactory;

    /**
     * @var MetaIssueNotification
     */
    private $issueNotification;

    /**
     * Class setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->authenticator = $this->createMock(Authenticator::class);
        $this->catalogConfigUpdateHelper = $this->createMock(CatalogConfigUpdateHelper::class);
        $this->graphApiAdapter = $this->createMock(GraphAPIAdapter::class);
        $this->coreConfigFactory = $this->createMock(CoreConfigFactory::class);
        $this->issueNotification = $this->createMock(MetaIssueNotification::class);

        $objectManager = new ObjectManager($this);
        $this->settingsWebhookListenerImplMockObj = $objectManager->getObject(
            SettingsWebhookListenerImpl::class,
            [
                'systemConfig' => $this->systemConfig,
                'fbeHelper' => $this->fbeHelper,
                'collectionFactory' => $this->collectionFactory,
                'authenticator' => $this->authenticator,
                'catalogConfigUpdateHelper' => $this->catalogConfigUpdateHelper,
                'graphApiAdapter' => $this->graphApiAdapter,
                'coreConfigFactory' => $this->coreConfigFactory,
                'issueNotification' => $this->issueNotification
            ]
        );
    }

    /**
     * Validate if the settings webhook listener is working as expected
     *
     * @return void
     */
    public function testGetCoreConfig(): void
    {
        $reflection = new \ReflectionClass(SettingsWebhookListenerImpl::class);
        $configModulesProperty = $reflection->getProperty('collectionFactory');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->settingsWebhookListenerImplMockObj, $this->collectionFactory);

        $item1 = new DataObject(['scope_id' => '10']);
        $item2 = new DataObject(['scope_id' => '20']);
        $expectedItems = [$item1, $item2];

        $collection = $this->createMock(Collection::class);
        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $collection->expects($this->exactly(1))
            ->method('addValueFilter')
            ->willReturnSelf();
        $collection->expects($this->exactly(1))
            ->method('addFieldToSelect')
            ->willReturnSelf();
        $collection->expects($this->once())
            ->method('getItems')
            ->willReturn($expectedItems);

        $reflection = new \ReflectionClass(SettingsWebhookListenerImpl::class);
        $configModulesProperty = $reflection->getProperty('authenticator');
        $configModulesProperty->setAccessible(true);
        $configModulesProperty->setValue($this->settingsWebhookListenerImplMockObj, $this->authenticator);

        $this->authenticator->expects($this->once())
            ->method('authenticateRequestDangerouslySkipSignatureValidation')
            ->willReturnSelf();

        $externalBusinessId = '1234567890';
        $actual = $this->settingsWebhookListenerImplMockObj->getCoreConfig($externalBusinessId);
        $this->assertInstanceOf(CoreConfigInterface::class, $actual);
    }
}
