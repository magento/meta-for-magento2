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

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\MBEInstalls;
use Psr\Log\LoggerInterface;

class MbeUpdateInstalledConfig extends AbstractAjax implements HttpPostActionInterface
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var MBEInstalls
     */
    private $mbeInstalls;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param MBEInstalls $mbeInstalls
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param EventManager $eventManager
     *
     */
    public function __construct(
        Context          $context,
        JsonFactory      $resultJsonFactory,
        FBEHelper        $fbeHelper,
        MBEInstalls      $mbeInstalls,
        LoggerInterface  $logger,
        RequestInterface $request,
        EventManager     $eventManager
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
        $this->mbeInstalls = $mbeInstalls;
        $this->request = $request;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * Execute for json
     *
     * Run actual processing after request validation.
     * Only public to allow for more direct unit testing.
     *
     * @inheritdoc
     */
    public function executeForJson()
    {
        $storeId = $this->request->getParam('storeId');
        $triggerPostOnboarding = $this->request->getParam('triggerPostOnboarding') === 'true';
        if ($storeId === null) {
            return [
                'success' => false,
            ];
        }
        try {
            $this->logger->info('Starting updating Meta MBE Config...');
            $this->mbeInstalls->updateMBESettings($storeId);
            
            if ($triggerPostOnboarding) {
                $this->logger->info('Trigger facebook_fbe_onboarding_after '
                    . 'for Store {$storeId} after MBE Config Update');
                $this->eventManager->dispatch('facebook_fbe_onboarding_after', ['store_id' => $storeId]);
            }
            $response = [
                'success' => true,
            ];
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'update_mbe_config',
                    'event_type' => 'update_mbe_config'
                ]
            );
            $response = [
                'success' => false,
            ];
        }
        return $response;
    }
}
