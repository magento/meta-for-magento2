<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\Catalog\Controller\Adminhtml\Diagnostic;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
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
   * Load the page defined in view/adminhtml/layout/fbeadmin_diagnostic_index.xml
   *
   * @return Page
   */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Meta_Catalog::diagnostic');
        $resultPage->getConfig()->getTitle()->prepend(__('Catalog Diagnostics'));
        return $resultPage;
    }
}
