<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\Api\SettingsWebhookRequestImpl;
use Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface;

class SettingsWebhookRequestImplTest extends TestCase
{
    /**
     * @var SettingsWebhookRequestImpl
     */
    private $settingsWebhookRequestImpl;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsWebhookRequestImpl = $this->getMockBuilder(SettingsWebhookRequestImpl::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Validate if the settings external business ID is working
     * 
     * @return void
     */
    public function testSetExternalBusinessId(): void
    {
        $externalBusinessId = '12345';
        $this->settingsWebhookRequestImpl->setExternalBusinessId($externalBusinessId);

        $this->settingsWebhookRequestImpl->method('getExternalBusinessId')
            ->willReturn($externalBusinessId);
        
        $this->assertEquals($externalBusinessId, $this->settingsWebhookRequestImpl->getExternalBusinessId());
    }

    /**
     * Validate if the settings notification is working
     * 
     * @return void
     */
    public function testSetNotification(): void
    {
        $notification = $this->createMock(MetaIssueNotificationInterface::class);
        $notification->setSeverity(1);
        $notification->setMessage('Test message');
        $notification->setNotificationId('12345');

        $this->settingsWebhookRequestImpl->setNotification($notification);

        $this->settingsWebhookRequestImpl->method('getNotification')
            ->willReturn($notification);
        
        $this->assertEquals($notification, $this->settingsWebhookRequestImpl->getNotification());
        $this->assertSame($notification->getMessage(), $this->settingsWebhookRequestImpl->getNotification()->getMessage());
        $this->assertSame($notification->getSeverity(), $this->settingsWebhookRequestImpl->getNotification()->getSeverity());
        $this->assertSame($notification->getNotificationId(), $this->settingsWebhookRequestImpl->getNotification()->getNotificationId());
    }
}