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

namespace Meta\Catalog\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\RequestInterface as Request;

class Catalog extends Value
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SystemConfig $systemConfig
     * @param Request $request
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SystemConfig $systemConfig,
        Request $request,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->systemConfig = $systemConfig;
        $this->request = $request;
    }

    /**
     * Delete feed id if Catalog value changed before save
     *
     * @return $this|Catalog
     */
    public function beforeSave()
    {
        if ($this->isValueChanged()) {
            $storeId = $this->request->getParam('store');
            $this->systemConfig->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $storeId);
        }
        return $this;
    }
}
