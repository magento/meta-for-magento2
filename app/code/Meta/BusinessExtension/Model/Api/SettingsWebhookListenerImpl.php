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

namespace Meta\BusinessExtension\Model\Api;

use Exception;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Api\CoreConfigInterface;
use Meta\BusinessExtension\Api\CustomApiKey\UnauthorizedTokenException;
use Meta\BusinessExtension\Api\Data\MetaIssueNotificationInterface;
use Meta\BusinessExtension\Api\SettingsWebhookListenerInterface;
use Meta\BusinessExtension\Api\SettingsWebhookRequestInterface;
use Meta\BusinessExtension\Helper\CatalogConfigUpdateHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification;

use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SettingsWebhookListenerImpl implements SettingsWebhookListenerInterface
{
    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var CatalogConfigUpdateHelper
     */
    private CatalogConfigUpdateHelper $catalogConfigUpdateHelper;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /** @var Authenticator */
    private Authenticator $authenticator;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphApiAdapter;

    /**
     * @var CoreConfigFactory
     */
    private CoreConfigFactory $coreConfigFactory;

    /**
     * @var MetaIssueNotification
     */
    private $issueNotification;

    /**
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $collectionFactory
     * @param Authenticator $authenticator
     * @param CatalogConfigUpdateHelper $catalogConfigUpdateHelper
     * @param GraphAPIAdapter $graphApiAdapter
     * @param CoreConfigFactory $coreConfigFactory
     * @param MetaIssueNotification $issueNotification
     */
    public function __construct(
        SystemConfig               $systemConfig,
        FBEHelper                 $fbeHelper,
        CollectionFactory         $collectionFactory,
        Authenticator             $authenticator,
        CatalogConfigUpdateHelper  $catalogConfigUpdateHelper,
        GraphAPIAdapter           $graphApiAdapter,
        CoreConfigFactory          $coreConfigFactory,
        MetaIssueNotification      $issueNotification
    ) {
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
        $this->collectionFactory = $collectionFactory;
        $this->authenticator = $authenticator;
        $this->catalogConfigUpdateHelper = $catalogConfigUpdateHelper;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->coreConfigFactory = $coreConfigFactory;
        $this->issueNotification = $issueNotification;
    }

    /**
     * Process webhook POST request
     *
     * @param SettingsWebhookRequestInterface[] $settingsWebhookRequest
     * @return void
     * @throws UnauthorizedTokenException
     * @throws LocalizedException
     */
    public function processSettingsWebhookRequest(array $settingsWebhookRequest): void
    {
        $this->authenticator->authenticateRequest();
        foreach ($settingsWebhookRequest as $setting) {
            $this->updateSetting($setting);
        }
    }
    /**
     * Update notification in magento Admin page
     *
     * @param MetaIssueNotificationInterface $notification
     */
    private function processNotification(MetaIssueNotificationInterface $notification): void
    {
        $this->issueNotification->deleteByNotificationId(MetaIssueNotification::VERSION_NOTIFICATION_ID);
        if (empty($notification->getMessage())) {
            return;
        }
        $this->issueNotification->saveVersionNotification($notification);
    }

    /**
     * Process webhook POST request
     *
     * @param SettingsWebhookRequestInterface $setting
     * @throws LocalizedException
     */
    private function updateSetting(SettingsWebhookRequestInterface $setting): void
    {
        // Step 0 - If it has notification, process and end.
        $notification = $setting->getNotification();
        if ($notification !== null) {
            $this->processNotification($notification);
            return;
        }

        // Step 1 - Get StoreId by business_extension_id
        $externalBusinessId = $setting->getExternalBusinessId();
        $storeId = $this->getStoreIdByExternalBusinessId($externalBusinessId);

        try {
            // Step 2 - Trigger Magento polling Graph API fbe_install,
            $fbeResponse = $this->getMBESettings((int)$storeId);
            // Step 3 - calling Catalog Script to update
            $this->catalogConfigUpdateHelper
                ->updateCatalogConfiguration(
                    (int)$storeId,
                    $fbeResponse['catalog_id'],
                    $fbeResponse['commerce_partner_integration_id'],
                    $fbeResponse['pixel_id'],
                );
            // Step 4 - Verify Catalog id updated correctly
            if ($this->systemConfig->getCatalogId((int)$storeId) !== $fbeResponse['catalog_id']) {
                $this->throwException('Catalog config update failed for external_business_id: '.$externalBusinessId);
            }
        } catch (Throwable $e) {
            $context = [
                'store_id' => $storeId,
                'event' => 'update_setting',
                'event_type' => 'settings_sync',
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
            $this->throwException($e->getMessage());
        }
    }

    /**
     * Get storeId
     *
     * @param  string $externalBusinessId
     * @return string
     * @throws LocalizedException
     */
    private function getStoreIdByExternalBusinessId(string $externalBusinessId): string
    {
        $installedConfigs = $this->getMBEInstalledConfigsByExternalBusinessId($externalBusinessId);
        if (empty($installedConfigs)) {
            $this->throwException('No store id is found for found for external_business_id: '.$externalBusinessId);
        }
        // StoreId and externalBusinessId is 1:1 mapping, hence get $storeIds[0] as $storeId in below.
        return $installedConfigs[0]->getScopeId();
    }

    /**
     * Polling from fbe_install Graph API
     *
     * @param int $storeId
     * @return string[]
     * @throws LocalizedException
     */
    private function getMBESettings(int $storeId): array
    {
        $accessToken = $this->systemConfig->getAccessToken($storeId);
        $businessId = $this->systemConfig->getExternalBusinessId($storeId);
        if (!$accessToken || !$businessId) {
            $this->throwException('AccessToken or BusinessID not found for storeID:'.$storeId);
        }
        $response = $this->graphApiAdapter->getFBEInstalls($accessToken, $businessId);
        if (!is_array($response) || empty($response)) {
            $this->throwException('Skipping FBEInstalls save. Response format is incorrect.');
        }
        $data = $response['data'][0];
        return [
            'catalog_id' => $data['catalog_id'] ?? '',
            'commerce_partner_integration_id' => $data['commerce_partner_integration_id'] ?? '',
            'pixel_id' => $data['pixel_id'] ?? '',
        ];
    }

    /**
     * Exception helper
     *
     * @param string $errorMessage
     * @throws LocalizedException
     */
    private function throwException(string $errorMessage)
    {
        throw new LocalizedException(__(
            $errorMessage
        ));
    }

    /**
     * Get config values where MBE is installed for $externalBusinessId
     *
     * @param string $externalBusinessId
     * @return array
     */
    private function getMBEInstalledConfigsByExternalBusinessId(string $externalBusinessId): array
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection
                ->addFieldToFilter('scope', ['eq' => 'stores'])
                ->addFieldToFilter(
                    'path',
                    ['eq' => SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_EXTERNAL_BUSINESS_ID]
                )
                ->addValueFilter($externalBusinessId)
                ->addFieldToSelect('scope_id');

            return $collection->getItems();

        } catch (Exception $e) {
            $this->fbeHelper->logException($e);
            return [];
        }
    }

    /**
     * Process webhook GET request to pull core config from Magento to Meta
     *
     * @param string $externalBusinessId
     * @return \Meta\BusinessExtension\Api\CoreConfigInterface
     * @throws LocalizedException
     */
    public function getCoreConfig(string $externalBusinessId): CoreConfigInterface
    {
        $storeId = $this->getStoreIdByExternalBusinessId($externalBusinessId);
        $coreConfig = $this->coreConfigFactory->create();
        try {
            $this->authenticator->authenticateRequest();
            $coreConfigData =  $this->getCoreConfigByStoreId($externalBusinessId, $storeId);
            $coreConfig->addData($coreConfigData);
        } catch (Exception $e) {
            $context = [
                'store_id' => $storeId,
                'event' => 'get_core_config',
                'event_type' => 'settings_sync',
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
            $this->throwException($e->getMessage());
        }
        return $coreConfig;
    }

    /**
     * Fetch core config by $storeId
     *
     * @param string $externalBusinessId
     * @param string $storeId
     * @return array
     */
    private function getCoreConfigByStoreId(string $externalBusinessId, string $storeId): array
    {
        return [
            'externalBusinessId' => $externalBusinessId,
            'isOrderSyncEnabled' => $this->systemConfig->isOrderSyncEnabled($storeId),
            'isCatalogSyncEnabled' => $this->systemConfig->isCatalogSyncEnabled($storeId),
            'isPromotionsSyncEnabled' => $this->systemConfig->isPromotionsSyncEnabled($storeId),
            'isOnsiteCheckoutEnabled' =>  $this->systemConfig->isOnsiteCheckoutEnabled($storeId),
            'productIdentifierAttr' => $this->systemConfig->getProductIdentifierAttr($storeId),
            'outOfStockThreshold' => $this->systemConfig->getOutOfStockThreshold($storeId),
            'isCommerceExtensionEnabled' => $this->systemConfig->isCommerceExtensionEnabled($storeId),
            'feedId' => $this->systemConfig->getFeedId($storeId),
            'installedMetaExtensionVersion' => $this->systemConfig->getModuleVersion(),
            'graphApiVersion' => $this->graphApiAdapter->getGraphApiVersion(),
            'magentoVersion' => $this->fbeHelper->getMagentoVersion(),
        ];
    }
}
