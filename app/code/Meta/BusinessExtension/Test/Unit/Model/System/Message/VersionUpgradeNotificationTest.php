<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\System\Message;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Escaper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Phrase;
use Meta\BusinessExtension\Model\System\Message\VersionUpgradeNotification;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification;

class VersionUpgradeNotificationTest extends TestCase
{
    /**
     * @var VersionUpgradeNotification
     */
    private $versionUpgradeNotification;

    /**
     * @var MetaIssueNotification|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metaIssueNotification;

    /**
     * @var Escaper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $escaper;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlBuilder;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $request;

    /**
     * @var array
     */
    private $notificationData = [
        'message' => 'Test message',
        'notification_id' => '12345',
        'severity' => '1'
    ];

    /**
     * Class setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->escaper = $this->createMock(Escaper::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->metaIssueNotification = $this->createMock(MetaIssueNotification::class);

        $this->versionUpgradeNotification = new VersionUpgradeNotification(
            $this->metaIssueNotification,
            $this->escaper,
            $this->urlBuilder,
            $this->request
        );

        $this->initializeLoadVersionNotificationReturn($this->notificationData);
    }

    /**
     * Test getIdentity method
     *
     * @return void
     */
    public function testGetIdentity(): void
    {
        $identity = $this->versionUpgradeNotification->getIdentity();
        $this->assertEquals($this->notificationData['notification_id'], $identity);
    }

    /**
     * Test isDisplayed method
     *
     * @return void
     */
    public function testIsDisplayed(): void
    {
        $isDisplayed = $this->versionUpgradeNotification->isDisplayed();
        $this->assertTrue($isDisplayed);
    }

    /**
     * Test getText method
     *
     * @return void
     */
    public function testGetText(): void
    {
        $this->escaper->method('escapeHtml')
            ->with($this->notificationData['message'])
            ->willReturn($this->notificationData['message']);
            
        $actualText = $this->versionUpgradeNotification->getText();
        
        $this->assertEquals($this->getNotificationExpectedText(), $actualText);
    }

    /**
     * Test getSeverity
     *
     * @return void
     */
    public function testGetSeverity(): void
    {
        $actualResult = $this->versionUpgradeNotification->getSeverity();

        $this->assertEquals($this->notificationData['severity'], $actualResult);
    }

    /**
     * Get getText
     *
     * @return Phrase
     */
    private function getNotificationExpectedText(): Phrase
    {
        $link_html_open = '<a href="https://fb.me/meta-extension">';
        $link_html_close = '</a>';
        return __(
            '%1 %2Open Adobe Commerce Marketplace%3',
            'Test message',
            $link_html_open,
            $link_html_close
        );
    }

    /**
     * Initialize the Nofication Return Data
     *
     * @return void
     */
    private function initializeLoadVersionNotificationReturn($notificationData): void
    {
        $this->metaIssueNotification->expects($this->once())
            ->method('loadVersionNotification')
            ->willReturn($notificationData);
    }
}
