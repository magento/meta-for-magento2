<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbaamsettings extends AbstractAjax
{
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
