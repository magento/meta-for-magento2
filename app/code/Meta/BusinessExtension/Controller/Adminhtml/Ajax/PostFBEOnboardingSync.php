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
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Throwable;

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
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * Construct
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param EventManager $eventManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        EventManager $eventManager
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->eventManager = $eventManager;
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
            $storeName = $this->systemConfig->getStoreManager()->getStore($storeId)->getName();
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
            // Dispatch the facebook_post_fbe_onboarding_sync event,
            // so observers in other Meta modules can subscribe and trigger their syncs,
            // such as full catalog sync, and shipping profiles sync
            $this->eventManager->dispatch('facebook_fbe_onboarding_after', ['store_id' => $storeId]);

            $response['success'] = true;
            $response['message'] = 'Post FBE Onboarding Sync successful';
            return $response;
        } catch (Throwable $e) {
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
