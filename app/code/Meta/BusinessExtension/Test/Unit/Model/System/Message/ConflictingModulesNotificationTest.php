<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\System\Message;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\System\Message\ConflictingModulesNotification;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Notification\MessageInterface;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification;

class ConflictingModulesNotificationTest extends TestCase
{
    /**
     * @var array
     */
    private static array $conflictingModules = ['Apptrian_MetaPixelApi'];

    /**
     * @var ConflictingModulesNotification
     */
    private $conflictingModulesNotification;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleManager = $this->getMockBuilder(ModuleManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->metaIssueNotification = $this->getMockBuilder(MetaIssueNotification::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->conflictingModulesNotification = new ConflictingModulesNotification(
            $this->metaIssueNotification,
            $this->moduleManager
        );
    }

    /**
     * Test the getIdentity method
     * 
     * @return void
     */
    public function testGetIdentity(): void
    {
        $this->metaIssueNotification->expects($this->once())
            ->method('loadVersionNotification')
            ->willReturn(['notification_id' => 'test_notification_id']);

        $result = $this->conflictingModulesNotification->getIdentity();

        $this->assertEquals('test_notification_id', $result);
    }

    /**
     * Test the getIdentity method when no notification ID is found
     * 
     * @return void
     */
    public function testGetIdentityReturnsEmpty(): void
    {
        $this->metaIssueNotification->expects($this->once())
            ->method('loadVersionNotification')
            ->willReturn([]);

        $result = $this->conflictingModulesNotification->getIdentity();

        $this->assertEmpty($result);
    }

    /**
     * Test the isDisplayed method when the conflicting module is enabled
     * 
     * @return void
     */
    public function testIsDisplayedReturnTrue(): void
    {
        $this->modifyParentClassPrivatePropertyAccess('conflictingModules', self::$conflictingModules);

        $this->moduleManager->expects($this->once())
            ->method('isEnabled')
            ->with(self::$conflictingModules[0])
            ->willReturnMap([
                [self::$conflictingModules[0], true],
            ]);

        $result = $this->conflictingModulesNotification->isDisplayed();

        $this->assertTrue($result);
    }

    /**
     * Test the isDisplayed method when the conflicting module is not enabled
     * 
     * @return void
     */
    public function testIsDisplayedReturnFalse(): void
    {
        $this->modifyParentClassPrivatePropertyAccess('conflictingModules', self::$conflictingModules);

        $this->moduleManager->expects($this->once())
            ->method('isEnabled')
            ->with(self::$conflictingModules[0])
            ->willReturnMap([
                [self::$conflictingModules[0], false],
            ]);

        $result = $this->conflictingModulesNotification->isDisplayed();

        $this->assertFalse($result);
    }

    /**
     * Test the getText method
     * 
     * @return void
     */
    public function testGetText(): void
    {
        $this->modifyParentClassPrivatePropertyAccess('conflictingModuleFound', self::$conflictingModules[0]);

        $result = $this->conflictingModulesNotification->getText();

        $this->assertEquals(
            $this->getConflectText(self::$conflictingModules[0]),
            $result
        );
    }

    public function testGetSeverity()
    {
        $this->assertEquals(
            MessageInterface::SEVERITY_CRITICAL,
            $this->conflictingModulesNotification->getSeverity()
        );
    }

    /**
     * Modify the conflictingModules property in the parent class
     * 
     * @return void
     */
    private function modifyParentClassPrivatePropertyAccess($variable, $setValue): void
    {
        $reflection = new \ReflectionClass(ConflictingModulesNotification::class);
        $conflictingModulesProperty = $reflection->getProperty($variable);
        $conflictingModulesProperty->setAccessible(true);
        $conflictingModulesProperty->setValue($this->conflictingModulesNotification, $setValue);
    }

    /**
     * Get the message text
     * 
     * @param string $conflictingModuleFound
     * @return string
     */
    private function getConflectText($conflictingModuleFound): string
    {
        return sprintf('The following module conflicts with the Facebook & Instagram Extension: [%s].
            Please disable the conflicting module.', $conflictingModuleFound);
    }
}