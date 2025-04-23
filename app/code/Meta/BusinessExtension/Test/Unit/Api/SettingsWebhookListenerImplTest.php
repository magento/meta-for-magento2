<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use Meta\BusinessExtension\Model\Api\SettingsWebhookListenerImpl;
use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Api\CoreConfigInterface;
use Meta\BusinessExtension\Model\Api\CoreConfigFactory;
use Meta\BusinessExtension\Model\Api\CoreConfig;

class SettingsWebhookListenerImplTest extends TestCase
{
    /**
     * @var SettingsWebhookListenerImpl
     */
    private $settingsWebhookListenerImpl;

    /**
     * @var CoreConfigInterface
     */
    private $mockCoreConfigInterface;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsWebhookListenerImpl = $this->getMockBuilder(SettingsWebhookListenerImpl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockCoreConfigFactory = $this->createMock(CoreConfigFactory::class);
        $this->mockCoreConfig = $this->createMock(CoreConfig::class);
    }

    /**
     * Validate if the settings webhook listener is working as expected
     * 
     * @return void
     */
    public function testGetCoreConfig(): void
    {
        $this->mockCoreConfigFactory
            ->method('create')
            ->willReturn($this->mockCoreConfig);

        $externalBusinessId = '1234567890';
        $actual = $this->settingsWebhookListenerImpl->getCoreConfig($externalBusinessId);
        $this->assertInstanceOf(CoreConfigInterface::class, $actual);
    }
}