<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

/**
 * Pulls pending orders from FB Commerce Account using FB Graph API
 */
class SyncOrders
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var CommerceHelper
     */
    private $commerceHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param CommerceHelper $commerceHelper
     */
    public function __construct(SystemConfig $systemConfig, CommerceHelper $commerceHelper)
    {
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
    }

    public function execute()
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveOrderSync())) {
            return;
        }

        $this->commerceHelper->pullOrders();
    }
}
