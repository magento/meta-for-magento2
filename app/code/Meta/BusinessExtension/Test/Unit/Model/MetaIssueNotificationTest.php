<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\MetaIssueNotification;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification as ResourceModel;

class MetaIssueNotificationTest extends TestCase
{
    /**
     * Class Setup
     * 
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->abstractResource = $this->createMock(ResourceModel::class);
        $this->abstractDb = $this->createMock(AbstractDb::class);

        $this->abstractResource->expects($this->any())
            ->method('getIdFieldName')
            ->willReturn('entity_id');

        $this->metaIssueNoticiationMockObj = new MetaIssueNotification(
            $this->context,
            $this->registry,
            $this->abstractResource,
            $this->abstractDb,
            []
        );
    }

    /**
     * Test setSeverity function
     * 
     * @return void
     */
    public function testSetSeverity(): void
    {
        $severity = 1;
        $result = $this->metaIssueNoticiationMockObj->setSeverity($severity);
        $this->assertEquals($severity, $this->metaIssueNoticiationMockObj->getSeverity());
    }

    /**
     * Test setMessage function
     * 
     * @return void
     */
    public function testSetMessage(): void
    {
        $message = 'PHP Unit Test';
        $result = $this->metaIssueNoticiationMockObj->setMessage($message);
        $this->assertEquals($message, $this->metaIssueNoticiationMockObj->getMessage());
    }

    /**
     * Test getMessage function
     * 
     * @return void
     */
    public function testGetMessage(): void
    {
        $this->assertNull($this->metaIssueNoticiationMockObj->getMessage());
    }

    /**
     * Test getNotificationId function
     * 
     * @return void
     */
    public function testGetNotificationId(): void
    {
        $this->assertNull($this->metaIssueNoticiationMockObj->getNotificationId());
    }

    /**
     * Test setNotificationId function
     * 
     * @return void
     */
    public function testSetNotificationId(): void
    {
        $notificationId = '123';
        $result = $this->metaIssueNoticiationMockObj->setNotificationId($notificationId);
        $this->assertEquals($notificationId, $this->metaIssueNoticiationMockObj->getNotificationId());
    }

    /**
     * Test setNotificationId function
     * 
     * @return void
     */
    public function testSetNotificationIdReturnObject(): void
    {
        $notificationId = '123';
        $result = $this->metaIssueNoticiationMockObj->setNotificationId($notificationId);
        $this->assertInstanceOf(MetaIssueNotification::class, $result);
    }
}