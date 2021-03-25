<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\System\Comment;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Config\Model\Config\CommentInterface;

class AccessToken implements CommentInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        if (!$elementValue) {
            return '';
        }
        return '<a href="https://developers.facebook.com/tools/debug/accesstoken?access_token=' . $elementValue . '" target="_blank" title="Debug" style="color:#2b7dbd">Debug</a>'
            . ' | <a href="https://developers.facebook.com/tools/explorer?access_token=' . $elementValue . '" target="_blank" title="Try in Graph API Explorer" style="color:#2b7dbd">Try in Graph API Explorer</a>';
    }
}
