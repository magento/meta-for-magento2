<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

class Notification implements MessageInterface
{
    public function getIdentity()
    {
        return 'facebook_notification';
    }

    public function isDisplayed()
    {
        // unimplemented
        return false;
    }

    public function getText()
    {
        return 'This is a notification from Facebook';
    }

    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }
}
