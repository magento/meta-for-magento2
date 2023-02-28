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

namespace Meta\BusinessExtension\Model\System\Comment;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Config\Model\Config\CommentInterface;

class AccessToken implements CommentInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * AccessToken constructor.
     *
     * @param SystemConfig $systemConfig
     */
    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * Get comment text
     *
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        if (!$elementValue) {
            return '';
        }
        return '<a href="https://developers.facebook.com/tools/debug/accesstoken?access_token='
            . $elementValue . '" target="_blank" title="Debug" style="color:#2b7dbd">Debug</a>'
            . ' | <a href="https://developers.facebook.com/tools/explorer?access_token=' . $elementValue
            . '" target="_blank" title="Try in Graph API Explorer" style="color:#2b7dbd">Try in Graph API Explorer</a>';
    }
}
