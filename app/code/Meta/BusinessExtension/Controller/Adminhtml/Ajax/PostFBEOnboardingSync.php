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

namespace Meta\BusinessExtension\Controller\Adminhtml\Ajax;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Helper\CatalogSyncHelper;
use Magento\Backend\App\Action\Context;
use Meta\Sales\Plugin\ShippingSyncer;
use Magento\Framework\Controller\Result\JsonFactory;

class PostFBEOnboardingSync extends AbstractAjax
{
    private const ACCESS_TOKEN_NOT_SET_ERROR_MESSAGE =
        'Access token is not successfully set after FBE onboarding for store';

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var CatalogSyncHelper
     */
    private $catalogSyncHelper;

    /**
     * @var ShippingSyncer
     */
    private $shippingSyncer;

    /**
     * Construct
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param CatalogSyncHelper $catalogSyncHelper
     * @param ShippingSyncer $shippingSyncer
     */
    public function __construct(
        Context           $context,
        JsonFactory       $resultJsonFactory,
        FBEHelper         $fbeHelper,
        SystemConfig      $systemConfig,
        CatalogSyncHelper $catalogSyncHelper,
        ShippingSyncer    $shippingSyncer
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->catalogSyncHelper = $catalogSyncHelper;
        $this->shippingSyncer = $shippingSyncer;
    }

    /**
     * Execute for json
     *
     * @return array
     */
    public function executeForJson(): array
    {
        $storeId = (int)$this->getRequest()->getParam('storeId');
        if (!$storeId) {
            $response['success'] = false;
            $response['message'] = __('StoreId param is not set for Post FBE onboarding sync');
            $this->fbeHelper->log('StoreId param is not set for Post FBE onboarding sync');
            return $response;
        }

        try {
            $store = $this->systemConfig->getStoreManager()->getStore($storeId);
            $storeName = $store->getName();
            if (!$this->systemConfig->getAccessToken($storeId)) {
                $response['success'] = false;
                $response['message'] = __(
                    '%1 %2',
                    self::ACCESS_TOKEN_NOT_SET_ERROR_MESSAGE,
                    $storeName
                );
                $this->fbeHelper->log(sprintf(
                    '%s %s',
                    self::ACCESS_TOKEN_NOT_SET_ERROR_MESSAGE,
                    $storeName
                ));
                return $response;
            }

            // Immediately after onboarding we initiate full catalog sync.
            // It syncs all products and all categories to Meta Catalog
            $this->catalogSyncHelper->syncFullCatalog($storeId);
            $this->shippingSyncer->syncShippingProfiles('post_fbe_onboarding', $store);

            $response['success'] = true;
            $response['message'] = 'Post FBE Onboarding Sync successful';
            return $response;
        } catch (\Throwable $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'post_fbe_onboarding',
                    'event_type' => 'post_fbe_onboarding_sync'
                ]
            );
            return $response;
        }
    }
}
