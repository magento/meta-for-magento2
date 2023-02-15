<?php

/**
 * Field renderer for hidden fields
 */

declare(strict_types=1);

namespace Meta\BusinessExtension\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Hidden extends Field
{
    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(Context $context, array $data = [], ?SecureHtmlRenderer $secureRenderer = null)
    {
        $secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
        parent::__construct($context, $data, $secureRenderer);
        $this->secureRenderer = $secureRenderer;
    }

    /**
     * Decorate field row html to be invisible
     *
     * @param AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(AbstractElement $element, $html)
    {
        return '<tr id="row_' . $element->getHtmlId() . '" >' . $html . '</tr>' .
            /* @noEscape */ $this->secureRenderer->renderStyleAsTag(
                "display: none;",
                'tr#row_' . $element->getHtmlId()
            );
    }
}
