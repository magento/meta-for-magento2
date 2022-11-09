<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbdeleteasset extends AbstractAjax
{
    /**
     * @return array
     */
    public function executeForJson()
    {
        return $this->_fbeHelper->deleteConfigKeys();
    }
}
