<?php

declare(strict_types=1);

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


namespace Meta\BusinessExtension\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * This config class allows us to configure the Graph API Base URL in a centralized and predictable way.
 */
class GraphAPIConfig extends AbstractHelper
{
    /**
     * Returns the correctly configured Graph API Base URL.
     *
     * @return string
     */
    public function getGraphBaseURL(): string
    {
        $baseURLOverride = $this->scopeConfig->getValue(
            'facebook/internal/graph_base_url',
            ScopeInterface::SCOPE_STORE
        );
        return $baseURLOverride ?? 'https://graph.facebook.com/';
    }
}
