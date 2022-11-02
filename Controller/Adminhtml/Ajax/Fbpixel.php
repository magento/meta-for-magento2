<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class Fbpixel extends AbstractAjax
{

    /**
     * @var SystemConfig
     */
    protected $systemConfig;
    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->systemConfig = $systemConfig;
    }

  // Yet to verify how to use the pii info, hence have commented the part of code.
    public function executeForJson()
    {
        $old_pixel_id = $this->systemConfig->getConfig('fbpixel/id');
        $response = [
        'success' => false,
        'pixelId' => $old_pixel_id
        ];
        $pixel_id = $this->getRequest()->getParam('pixelId');
        if ($pixel_id && $this->_fbeHelper->isValidFBID($pixel_id)) {
            $this->systemConfig->saveConfig('fbpixel/id', $pixel_id);
            $this->systemConfig->saveConfig('fbe/installed', true);
            $response['success'] = true;
            $response['pixelId'] = $pixel_id;
            if ($old_pixel_id != $pixel_id) {
                $this->_fbeHelper->log(sprintf("Pixel id updated from %d to %d", $old_pixel_id, $pixel_id));
                $datetime = $this->_fbeHelper->createObject(DateTime::class);
                $this->systemConfig->saveConfig(
                    'fbpixel/install_time',
                    $datetime->gmtDate('Y-m-d H:i:s')
                );
            }
        }
        return $response;
    }
}
