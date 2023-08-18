<?php
declare(strict_types=1);

namespace Meta\Catalog\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Meta\Catalog\Block\Adminhtml\Form\Field\ProductAttributeColumn;
use Meta\Catalog\Block\Adminhtml\Form\Field\MetaAttributeColumn;

/**
 * Create a block for meta attribute mapping
 */
class MetaAttributeMappingData extends AbstractFieldArray
{
    /**
     * @var ProductAttributeColumn
     */
    private $productAttributesRenderer;

    /**
     * @var MetaAttributeColumn
     */
    private $metaAttributesRenderer;

    /**
     * Prepare block renderer for meta attribute mapping
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'meta_attributes',
            [
                'label' => __('Meta attributes'),
                'class' => 'required-entry',
                'renderer' => $this->getMetaAttributesRenderer(),
            ]
        );
        $this->addColumn(
            'product_attributes',
            [
                'label' => __('Product Attributes'),
                'class' => 'required-entry',
                'renderer' => $this->getProductAttributesRenderer(),
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Mapping');
    }

    /**
     * Prepare Row
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        $dropdownField = $row->getCustomAttributeMapping();
        if ($dropdownField !== null) {
            $options['option_' . $this->getProductAttributesRenderer()->calcOptionHash($dropdownField)]
                = 'selected="selected"';
            $options['option_' . $this->getMetaAttributesRenderer()->calcOptionHash($dropdownField)]
                = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get Meta attribute renderer
     *
     * @return MetaAttributeColumn
     * @throws LocalizedException
     */
    private function getMetaAttributesRenderer(): MetaAttributeColumn
    {
        if (!$this->metaAttributesRenderer) {
            $this->metaAttributesRenderer = $this->getLayout()->createBlock(
                MetaAttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->metaAttributesRenderer;
    }

    /**
     * Get Product attribute renderer
     *
     * @return ProductAttributeColumn
     * @throws LocalizedException
     */
    private function getProductAttributesRenderer(): ProductAttributeColumn
    {
        if (!$this->productAttributesRenderer) {
            $this->productAttributesRenderer = $this->getLayout()->createBlock(
                ProductAttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->productAttributesRenderer;
    }
}
