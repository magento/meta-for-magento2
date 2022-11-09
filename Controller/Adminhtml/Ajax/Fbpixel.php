<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class Fbpixel extends AbstractAjax
{
    // Yet to verify how to use the pii info, hence have commented the part of code.
    public function executeForJson()
    {
        $oldPixelId = $this->systemConfig->getPixelId();
        $response = [
            'success' => false,
            'pixelId' => $oldPixelId
        ];
        $pixelId = $this->getRequest()->getParam('pixelId');
        if ($pixelId && $this->_fbeHelper->isValidFBID($pixelId)) {
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID, $pixelId);
            $this->systemConfig->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED, true);
            $response['success'] = true;
            $response['pixelId'] = $pixelId;
            if ($oldPixelId && $oldPixelId != $pixelId) {
                $this->_fbeHelper->log(sprintf("Pixel id updated from %d to %d", $oldPixelId, $pixelId));
            }
        }
        return $response;
    }
}
