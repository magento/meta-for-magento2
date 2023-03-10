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

namespace Meta\BusinessExtension\Block\Adminhtml;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Template;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

/**
 * @api
 */
class Setup extends Template
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var StoreRepositoryInterface
     */
    public $storeRepo;

    /**
     * @var WebsiteCollectionFactory
     */
    private $websiteCollectionFactory;

    /**
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param StoreRepositoryInterface $storeRepo
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        StoreRepositoryInterface $storeRepo,
        WebsiteCollectionFactory $websiteCollectionFactory,
        array $data = []
    ) {
        $this->fbeHelper = $fbeHelper;
        parent::__construct($context, $data);
        $this->systemConfig = $systemConfig;
        $this->storeRepo = $storeRepo;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
    }

    /**
     * Get pixel ajax route
     *
     * @return mixed
     */
    public function getPixelAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbpixel');
    }

    /**
     * Get access token ajax route
     *
     * @return mixed
     */
    public function getAccessTokenAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbtoken');
    }

    /**
     * Get profiles ajax route
     *
     * @return mixed
     */
    public function getProfilesAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbprofiles');
    }

    /**
     * Get aam settings route
     *
     * @return mixed
     */
    public function getAAMSettingsRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbaamsettings');
    }

    /**
     * Fetch pixel id
     *
     * @param int $storeId
     * @return string|null
     */
    public function fetchPixelId($storeId)
    {
        return $this->systemConfig->getPixelId($storeId);
    }

    /**
     * Get external business id
     *
     * @param int $storeId
     * @return string|null
     */
    public function getExternalBusinessId($storeId)
    {
        $storedExternalId = $this->systemConfig->getExternalBusinessId($storeId);
        if ($storedExternalId) {
            return $storedExternalId;
        }
        $storeId = $this->fbeHelper->getStore()->getId();
        $this->fbeHelper->log("Store id---" . $storeId);
        return uniqid('fbe_magento_' . $storeId . '_');
    }

    /**
     * Fetch configuration ajax route
     *
     * @return mixed
     */
    public function fetchConfigurationAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/persistConfiguration');
    }

    /**
     * Get delete asset ids ajax route
     *
     * @return mixed
     */
    public function getCleanCacheAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/cleanCache');
    }

    /**
     * Get Delete Asset IDs Ajax Route
     *
     * @return mixed
     */
    public function getDeleteAssetIdsAjaxRoute()
    {
        return $this->fbeHelper->getUrl('fbeadmin/ajax/fbdeleteasset');
    }

    /**
     * Get currency code
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->fbeHelper->getStoreCurrencyCode();
    }

    /**
     * Is fbe installed
     *
     * @param int $storeId
     * @return string
     */
    public function isFBEInstalled($storeId)
    {
        return $this->systemConfig->isFBEInstalled($storeId) ? 'true' : 'false';
    }

    /**
     * Get app id
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->systemConfig->getAppId();
    }

    /**
     * Get stores
     *
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores()
    {
        return $this->storeRepo->getList();
    }

    /**
     * Get first website id
     *
     * @return int|null
     */
    public function getFirstWebsiteId()
    {
        $collection = $this->websiteCollectionFactory->create();
        $collection->addFieldToSelect('website_id')
            ->addFieldToFilter('code', ['neq' => 'admin']);
        $collection->getSelect()->order('website_id ASC')->limit(1);

        return $collection->getFirstItem()->getWebsiteId();
    }
}
