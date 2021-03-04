<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Registry;

class Common extends \Magento\Backend\Block\Template
{

    /**
     * @var Registry
     */
    protected $_registry;
    /**
     * @var FBEHelper
     */
    protected $_fbeHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $registry,
        FBEHelper $fbeHelper,
        array $data = []
    ) {
        $this->_registry = $registry;
        $this->_fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
    }
}
