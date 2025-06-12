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

class VersionUpgradeNotificationEmptyDataTest extends TestCase
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
    private $notificationEmptyData = [
        'message' => '',
        'severity' => ''
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

        $this->initializeLoadVersionNotificationReturn($this->notificationEmptyData);
    }

    /**
     * Test getIdentity method
     *
     * @return void
     */
    public function testGetIdentity(): void
    {
        $identity = $this->versionUpgradeNotification->getIdentity();
        $this->assertEmpty($identity);
    }

    /**
     * Test isDisplayed method
     * 
     * @return void
     */
    public function testIsDisplayed(): void
    {
        $isDisplayed = $this->versionUpgradeNotification->isDisplayed();
        $this->assertFalse($isDisplayed);
    }

    /**
     * Test getText method
     * 
     * @return void
     */
    public function testGetText(): void
    {
        $actualText = $this->versionUpgradeNotification->getText();
        
        $this->assertEmpty($actualText);
    }

    /**
     * Test getSeverity
     * 
     * @return void
     */
    public function testGetSeverity(): void
    {
        $actualResult = $this->versionUpgradeNotification->getSeverity();

        $this->assertEquals(0, $actualResult);
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