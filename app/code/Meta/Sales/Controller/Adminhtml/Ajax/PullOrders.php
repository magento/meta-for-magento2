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

namespace Meta\Sales\Controller\Adminhtml\Ajax;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meta\BusinessExtension\Controller\Adminhtml\Ajax\AbstractAjax;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Helper\CommerceHelper;

class PullOrders extends AbstractAjax
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     * @param CommerceHelper $commerceHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SystemConfig $systemConfig,
        FBEHelper $fbeHelper,
        CommerceHelper $commerceHelper
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
    }

    /**
     * Pull/sync orders from facebook
     *
     * @return array
     * @throws GuzzleException
     */
    public function executeForJson(): array
    {
        // get default store info
        $storeId = $this->fbeHelper->getStore()->getId();

        // override store if user switched config scope to non-default
        $storeParam = $this->getRequest()->getParam('store');
        if ($storeParam) {
            $storeId = $storeParam;
        }

        if (!$this->systemConfig->isActiveOrderSync($storeId)) {
            $response['success'] = false;
            $response['error_message'] = __('Enable order sync before pulling orders.');
            return $response;
        }

        try {
            return ['success' => true, 'response' => $this->commerceHelper->pullPendingOrders((int)$storeId)];
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $this->fbeHelper->logException($e);
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }
}
