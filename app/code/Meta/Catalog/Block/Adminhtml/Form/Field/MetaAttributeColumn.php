<?php
declare(strict_types=1);

namespace Meta\Catalog\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;

class MetaAttributeColumn extends Select
{
    /**
     * @var array
     */
    public $options = [];

    /**
     * Constructor meta attribute column
     *
     * @param Context $context
     * @param array $options
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $options = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->options = $options;
    }

    /**
     * Set meta attribute input name
     *
     * @param string $value
     * @return mixed
     */
    public function setInputName(string $value)
    {
        return $this->setName($value);
    }

    /**
     * Set meta attribute input id
     *
     * @param string $value
     * @return MetaAttributeColumn
     */
    public function setInputId(string $value)
    {
        return $this->setId($value);
    }

    /**
     * Render meta attribute
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            asort($this->options);
            $this->setOptions($this->options);
        }
        return parent::_toHtml();
    }

    /**
     * Get all meta attribute options
     *
     * @return array
     */
    public function getMetaOptions()
    {
        return $this->options;
    }
}
