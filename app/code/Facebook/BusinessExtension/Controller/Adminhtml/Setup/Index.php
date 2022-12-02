<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Setup;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
  /**
   * @var PageFactory
   */
    protected $resultPageFactory;

  /**
   * Constructor
   *
   * @param Context $context
   * @param PageFactory $resultPageFactory
   */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

  /**
   * Load the page defined in view/adminhtml/layout/fbeadmin_setup_index.xml
   *
   * @return Page
   */
    public function execute()
    {
        return $resultPage = $this->resultPageFactory->create();
    }
}
