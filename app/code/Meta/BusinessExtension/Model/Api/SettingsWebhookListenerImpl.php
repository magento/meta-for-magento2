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
use Meta\BusinessExtension\Api\CustomApiKey\UnauthorizedTokenException;
use Meta\BusinessExtension\Api\SettingsWebhookListenerInterface;
use Meta\BusinessExtension\Api\SettingsWebhookRequestInterface;
use Meta\BusinessExtension\Helper\CatalogConfigUpdateHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Throwable;

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
     * @param SystemConfig $systemConfig
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $collectionFactory
     * @param Authenticator $authenticator
     * @param CatalogConfigUpdateHelper $catalogConfigUpdateHelper
     * @param GraphAPIAdapter $graphApiAdapter
     */
    public function __construct(
        SystemConfig              $systemConfig,
        FBEHelper                 $fbeHelper,
        CollectionFactory         $collectionFactory,
        Authenticator             $authenticator,
        CatalogConfigUpdateHelper $catalogConfigUpdateHelper,
        GraphAPIAdapter           $graphApiAdapter
    ) {
        $this->systemConfig = $systemConfig;
        $this->fbeHelper = $fbeHelper;
        $this->collectionFactory = $collectionFactory;
        $this->authenticator = $authenticator;
        $this->catalogConfigUpdateHelper = $catalogConfigUpdateHelper;
        $this->graphApiAdapter = $graphApiAdapter;
    }

    /**
     * Process webhook request
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
     * Process webhook request
     *
     * @param SettingsWebhookRequestInterface $setting
     * @throws LocalizedException
     */
    private function updateSetting(SettingsWebhookRequestInterface $setting): void
    {

        // Step 1 - Get StoreId by business_extension_id
        $externalBusinessId = $setting->getExternalBusinessId();
        $installedConfigs = $this->getMBEInstalledConfigsByExternalBusinessId($externalBusinessId);
        if (empty($installedConfigs)) {
            $this->throwException('No store id is found for found for external_business_id: '.$externalBusinessId);
        }
        // StoreId and externalBusinessId is 1:1 mapping, hence get $storeIds[0] as $storeId in below.
        $storeId = $installedConfigs[0]->getScopeId();

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
        try {
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
        } catch (Throwable $e) {
            $this->throwException('Failed to pull fbe response'.$e->getMessage());
        }
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
     * Get config values where MBE is installed for external_business_Id
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
}
