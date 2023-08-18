<?php
declare(strict_types=1);

namespace Meta\Catalog\Plugin\Backend\Config;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth;
use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Meta\Catalog\Block\Adminhtml\Form\Field\MetaAttributeColumn;
use Meta\Catalog\Block\Adminhtml\Form\Field\ProductAttributeColumn;

class SaveController
{
    /**
     * @var array
     */
    private $errorMessages = [];

    /**
     * @var array
     */
    private $attributeMappingData = [];

    /**
     * @var array
     */
    private $assignedMetaAttributes = [];

    /**
     * @var MetaAttributeColumn
     */
    private MetaAttributeColumn $metaAttributeColumn;

    /**
     * @var array
     */
    private $metaOptions;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var Structure
     */
    private Structure $configStructure;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var ProductAttributeColumn
     */
    private ProductAttributeColumn $productAttributeColumn;

    /**
     * @var array
     */
    private array $productOption;

    /** Constructor Save
     *
     * @param Context $context
     * @param Structure $configStructure
     * @param MetaAttributeColumn $metaAttributeColumn
     * @param ProductAttributeColumn $productAttributeColumn
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        MetaAttributeColumn $metaAttributeColumn,
        ProductAttributeColumn $productAttributeColumn
    ) {
        $this->metaAttributeColumn = $metaAttributeColumn;
        $this->metaOptions = $this->metaAttributeColumn->getMetaOptions();
        $this->messageManager = $context->getMessageManager();
        $this->configStructure = $configStructure;
        $this->auth = $context->getAuth();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->productAttributeColumn = $productAttributeColumn;
        $this->productOption = $this->productAttributeColumn->getProductOptions();
    }

    /**
     * Around plugin for save configuration
     *
     * @param Save $subject
     * @param \Closure $proceed
     * @return Redirect|mixed|void
     */
    public function aroundExecute(
        Save $subject,
        \Closure $proceed
    ) {
        try {
            $section = $subject->getRequest()->getParam('section');
            $website = $subject->getRequest()->getParam('website');
            $store = $subject->getRequest()->getParam('store');

            $configData = [
                'section' => $section,
                'website' => $website,
                'store' => $store,
                'groups' => $this->_getGroupsForSave($subject)
            ];

            if ($section === 'facebook_business_extension') {
                $this->attributeMappingData =
                    $configData['groups']['attribute_mapping']['fields']['custom_attribute_mapping']['value'];
                if (empty($this->attributeMappingData)) {
                    return $proceed();
                }
                $this->validateEmptyRecord();
                $this->validateUniqueRow();
                if (!empty($this->errorMessages)) {
                    foreach ($this->errorMessages as $message) {
                        $this->messageManager->addError($message);
                    }
                    $this->_saveState($subject->getRequest()->getPost('config_state'));
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath(
                        'adminhtml/system_config/edit',
                        [
                            '_current' => ['section', 'website', 'store'],
                            '_nosid' => true
                        ]
                    );
                }
            }
            return $proceed();
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong on attribute mapping configuration:') . ' ' . $e->getMessage()
            );
            $this->_saveState($subject->getRequest()->getPost('config_state'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath(
                'adminhtml/system_config/edit',
                [
                    '_current' => ['section', 'website', 'store'],
                    '_nosid' => true
                ]
            );
        }
    }

    /**
     * Save state of configuration field sets
     *
     * @param array $configState
     * @return bool
     */
    private function _saveState(array $configState = [])
    {
        if (is_array($configState)) {
            $configState = $this->sanitizeConfigState($configState);
            $adminUser = $this->auth->getUser();
            $extra = $adminUser->getExtra();
            if (!is_array($extra)) {
                $extra = [];
            }
            if (!isset($extra['configState'])) {
                $extra['configState'] = [];
            }
            foreach ($configState as $fieldset => $state) {
                $extra['configState'][$fieldset] = $state;
            }
            $adminUser->saveExtra($extra);
        }
        return true;
    }

    /**
     * Sanitize config state data
     *
     * @param array $configState
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @since 100.1.0
     */
    private function sanitizeConfigState(array $configState): array
    {
        $sectionList = $this->configStructure->getSectionList();
        $sanitizedConfigState = $configState;
        foreach ($configState as $sectionId => $value) {
            if (array_key_exists($sectionId, $sectionList)) {
                $sanitizedConfigState[$sectionId] = (bool)$sanitizedConfigState[$sectionId] ? '1' : '0';
            } else {
                unset($sanitizedConfigState[$sectionId]);
            }
        }
        return $sanitizedConfigState;
    }

    /**
     * Check unique records added for Meta attributes or not
     *
     * @return void
     */
    private function validateUniqueRow(): void
    {
        $this->assignedMetaAttributes = [];
        foreach ($this->attributeMappingData as $data) {
            if (isset($data['meta_attributes'])) {
                $this->assignedMetaAttributes[] = $data['meta_attributes'];
            }
        }

        if (count($this->assignedMetaAttributes)) {
            $duplicateRecords = $this->getDuplicates();
            asort($duplicateRecords);
            if (!empty($duplicateRecords)) {
                $tempRow = [];
                foreach ($duplicateRecords as $row => $metaCode) {
                    $tempRow[$metaCode][] = $row+1;
                }
                foreach ($tempRow as $code => $row) {
                    $this->errorMessages[] = __(
                        "Multiple assignment for Meta attributes <strong>'%1 '</strong>
                        found at Row <strong>%2</strong>. Keep only each Meta Attribute Mapping assignment.",
                        $this->metaOptions[$code],
                        implode(", ", $row)
                    );
                }
            }
        }
    }

    /**
     * Get Duplicates item of Meta attributes (case-sensitive)
     *
     * @return array
     */
    private function getDuplicates(): array
    {
        return array_intersect(
            $this->assignedMetaAttributes,
            array_unique(
                array_diff_key($this->assignedMetaAttributes, array_unique($this->assignedMetaAttributes))
            )
        );
    }

    /**
     * Get groups for save
     *
     * @param Save $subject
     * @return array|null
     */
    private function _getGroupsForSave(Save $subject)
    {
        $groups = $subject->getRequest()->getPost('groups');
        $files = $subject->getRequest()->getFiles('groups');

        if ($files && is_array($files)) {
            /**
             * Carefully merge $_FILES and $_POST information
             * None of '+=' or 'array_merge_recursive' can do this correct
             */
            foreach ($files as $groupName => $group) {
                $data = $this->_processNestedGroups($group);
                if (!empty($data)) {
                    if (!empty($groups[$groupName])) {
                        $groups[$groupName] = array_merge_recursive((array)$groups[$groupName], $data);
                    } else {
                        $groups[$groupName] = $data;
                    }
                }
            }
        }
        return $groups;
    }

    /**
     * Process nested groups
     *
     * @param mixed $group
     * @return array
     */
    private function _processNestedGroups(mixed $group): array
    {
        $data = [];

        if (isset($group['fields']) && is_array($group['fields'])) {
            foreach ($group['fields'] as $fieldName => $field) {
                if (!empty($field['value'])) {
                    $data['fields'][$fieldName] = ['value' => $field['value']];
                }
            }
        }

        if (isset($group['groups']) && is_array($group['groups'])) {
            foreach ($group['groups'] as $groupName => $groupData) {
                $nestedGroup = $this->_processNestedGroups($groupData);
                if (!empty($nestedGroup)) {
                    $data['groups'][$groupName] = $nestedGroup;
                }
            }
        }

        return $data;
    }

    /**
     * Check for empty field entered or not. Return error on empty field.
     *
     * @return void
     */
    private function validateEmptyRecord(): void
    {
        foreach ($this->attributeMappingData as $data) {
            $productAttributeCode = $data['product_attributes'] ?? "";
            $metaAttributeCode = $data['meta_attributes'] ?? "";
            $code = $this->metaOptions[$metaAttributeCode] ?? "";
            if (!isset($this->productOption[$productAttributeCode]) && $code !== "") {
                $this->errorMessages[] = __(
                    "Empty mapping found for Meta attribute: <strong>'%1 '</strong>",
                    $code
                );
            }
        }
    }
}
