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
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\App\Action\HttpDeleteActionInterface;
use Magento\Framework\App\RequestInterface;
use Meta\BusinessExtension\Model\MBEInstalls;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;
use Psr\Log\LoggerInterface;

class Fbdeleteasset extends AbstractAjax implements HttpDeleteActionInterface
{
    public const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Meta Business Extension." .
      " The pixel installed on your website is now deleted.";

    private const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
        Please try again.";

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var FacebookInstalledFeature
     */
    private $fbeInstalledFeatureResource;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @var MBEInstalls
     */
    private MBEInstalls $mbeInstalls;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Context                  $context
     * @param JsonFactory              $resultJsonFactory
     * @param FBEHelper                $fbeHelper
     * @param SystemConfig             $systemConfig
     * @param RequestInterface         $request
     * @param FacebookInstalledFeature $fbeInstalledFeatureResource
     * @param EventManager             $eventManager
     * @param MBEInstalls              $mbeInstalls
     * @param LoggerInterface          $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        RequestInterface $request,
        FacebookInstalledFeature $fbeInstalledFeatureResource,
        EventManager $eventManager,
        MBEInstalls $mbeInstalls,
        LoggerInterface $logger
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper);
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
        $this->request = $request;
        $this->fbeInstalledFeatureResource = $fbeInstalledFeatureResource;
        $this->eventManager = $eventManager;
        $this->mbeInstalls = $mbeInstalls;
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
        if ($storeId === null) {
            return [
                'success' => false,
                'error_message' => __('' . self::DELETE_FAILURE_MESSAGE)
            ];
        }
        try {
            $this->deleteInstalledFBE($storeId)
                ->deleteConfigKeys($storeId)
                ->deleteInstalledFeatures($storeId);

            $this->eventManager->dispatch('facebook_delete_assets_after', ['store_id' => $storeId]);

            $response = [
                'success' => true,
                'message' => __('' . self::DELETE_SUCCESS_MESSAGE),
            ];
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'delete_connection',
                    'event_type' => 'manual_deletion'
                ]
            );
            $response = [
                'success' => false,
                'error_message' => __(
                    "There was a problem deleting the connection. Please try again."
                ),
            ];
        }
        return $response;
    }

    /**
     * Delete config keys
     *
     * @param  string|int|null $storeId Store ID to delete from.
     * @return Fbdeleteasset
     */
    private function deleteConfigKeys($storeId)
    {
        $this->systemConfig->deleteConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID,
            $storeId
        )
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ACCESS_TOKEN, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $storeId)
            ->deleteConfig(
                SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_PARTNER_INTEGRATION_ID,
                $storeId
            )
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_IS_ONSITE_ELIGIBLE, $storeId);

        return $this;
    }

    /**
     * Delete installed features
     *
     * @param  string|int|null $storeId Store ID to delete from.
     * @return Fbdeleteasset
     */
    private function deleteInstalledFeatures($storeId)
    {
        $this->fbeInstalledFeatureResource->deleteAll($storeId);
        return $this;
    }

    /**
     * Delete Meta side FBE installation
     *
     * @param  string|int|null $storeId Store ID to delete from.
     * @return Fbdeleteasset
     */
    private function deleteInstalledFBE($storeId)
    {
        try {
            $this->mbeInstalls->deleteMBESettings($storeId);
        } catch (\Exception $e) {
            $this->logger->warning(
                "Failed to delete MBE installation for ".$storeId.". The installation may not exist"
            );
        }
        return $this;
    }
}
