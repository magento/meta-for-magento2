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

namespace Meta\Catalog\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class GoogleProductCategory extends AbstractSource
{
    /**
     * @var array
     */
    private $sourceData;

    /**
     * @param array $sourceData
     */
    public function __construct(array $sourceData)
    {
        $this->sourceData = $sourceData;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = $this->sourceData;
        }
        return $this->_options;
    }
}
