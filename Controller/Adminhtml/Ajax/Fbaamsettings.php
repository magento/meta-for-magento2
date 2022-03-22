<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbaamsettings extends AbstractAjax
{
    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
    }

    public function executeForJson()
    {
        $response = [
        'success' => false,
        'settings' => null,
        ];
        $pixelId = $this->getRequest()->getParam('pixelId');
        if ($pixelId) {
            $settingsAsString = $this->_fbeHelper->fetchAndSaveAAMSettings($pixelId);
            if ($settingsAsString) {
                $response['success'] = true;
                $response['settings'] = $settingsAsString;
            }
        }
        return $response;
    }
}
