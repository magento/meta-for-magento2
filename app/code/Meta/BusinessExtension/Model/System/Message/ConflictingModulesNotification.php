<?php


namespace Meta\BusinessExtension\Model\System\Message;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Notification\MessageInterface;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification;

class ConflictingModulesNotification implements MessageInterface
{
    /**
     * @var MetaIssueNotification
     */
    private MetaIssueNotification $metaIssueNotification;

    /**
     * @var ModuleManager
     */
    private ModuleManager $moduleManager;

    /**
     * @var array
     */
    private static array $conflictingModules = ['Apptrian_MetaPixelApi'];

    /**
     * @var string
     */
    private string $conflictingModuleFound = '';

    /**
     * Constructor
     *
     * @param MetaIssueNotification $metaIssueNotification
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        MetaIssueNotification $metaIssueNotification,
        ModuleManager $moduleManager
    ) {
        $this->metaIssueNotification = $metaIssueNotification;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get identity
     *
     * @return mixed|string
     */
    public function getIdentity()
    {
        $notification = $this->metaIssueNotification->loadVersionNotification();
        return $notification['notification_id'] ?? '';
    }

    /**
     * Toggle flag for displaying notification
     *
     * @return bool
     */
    public function isDisplayed(): bool
    {
        // iterate through the user's module manager to see if they have any conflicting modules
        foreach (self::$conflictingModules as $conflictingModule) {
            if ($this->moduleManager->isEnabled($conflictingModule)) {
                $this->conflictingModuleFound = $conflictingModule;
                return true;
            }
        }
        return false;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText(): string
    {
        return sprintf('The following module conflicts with the Facebook & Instagram Extension: [%s].
            Please disable the conflicting module.', $this->conflictingModuleFound);
    }

    /**
     * Get severity of the notification
     *
     * @return int
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_CRITICAL;
    }
}
