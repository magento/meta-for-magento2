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

namespace Meta\BusinessExtension\Controller\Adminhtml\Ajax;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\App\Action\HttpDeleteActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Security\Model\AdminSessionsManager;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\ResourceConnection;

class Fbdeleteasset implements HttpDeleteActionInterface
{
    public const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Meta Business Extension." .
      " The pixel installed on your website is now deleted.";

    private const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
        Please try again.";

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var AdminSessionsManager
     */
    private $adminSessionManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param AdminSessionsManager $adminSessionManager
     * @param ResourceConnection $resourceConnection
     * @param SystemConfig $systemConfig
     * @param RequestInterface $request
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        AdminSessionsManager $adminSessionManager,
        ResourceConnection $resourceConnection,
        SystemConfig $systemConfig,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fbeHelper = $fbeHelper;
        $this->adminSessionManager = $adminSessionManager;
        $this->resourceConnection = $resourceConnection;
        $this->systemConfig = $systemConfig;
        $this->request = $request;
    }

    /**
     * Execute
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        // TODO : Move all String objects to constants.
        $adminSession = $this->adminSessionManager->getCurrentSession();
        if (!$adminSession && $adminSession->getStatus() != 1) {
            throw new LocalizedException('Oops, this endpoint is for logged in admin and ajax only!');
        } else {
            try {
                $json = $this->executeForJson();
                return $result->setData($json);
            } catch (\Exception $e) {
                $this->fbeHelper->logCritical($e->getMessage());
                throw new LocalizedException(
                    'Oops, there was error while processing your request.' .
                    ' Please contact admin for more details.'
                );
            }
        }
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
            $this->deleteConfigKeys($storeId);
            $response = [
                'success' => true,
                'message' => __('' . self::DELETE_SUCCESS_MESSAGE),
            ];
        } catch (\Exception $e) {
            $this->fbeHelper->log($e->getMessage());
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
     * @param string|int|null $storeId Store ID to delete from.
     * @return array
     */
    private function deleteConfigKeys($storeId)
    {
        $this->systemConfig->saveConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INSTALLED,
            false,
            $storeId,
        );
        $this->systemConfig->deleteConfig(
            SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID,
            $storeId
        )
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ACCESS_TOKEN, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PIXEL_AAM_SETTINGS, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PROFILES, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION, $storeId)
            ->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_API_VERSION_LAST_UPDATE, $storeId);

        return $this;
    }
}
