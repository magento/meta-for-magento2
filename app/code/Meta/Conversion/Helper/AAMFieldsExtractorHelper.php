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

namespace Meta\Conversion\Helper;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Meta\BusinessExtension\Helper\FBEHelper;
use FacebookAds\Object\ServerSide\Normalizer;
use FacebookAds\Object\ServerSide\Util;
use FacebookAds\Object\ServerSide\Event;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\AddressFactory;

/**
 * Helper to extract and filter aam fields
 */
class AAMFieldsExtractorHelper
{
    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var CustomerMetadataInterface
     */
    private CustomerMetadataInterface $customerMetadata;

    /**
     * @var AddressFactory
     */
    private AddressFactory $addressFactory;

    /**
     * Constructor
     *
     * @param MagentoDataHelper $magentoDataHelper
     * @param FBEHelper $fbeHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param AddressFactory $addressFactory
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper,
        FBEHelper $fbeHelper,
        CustomerMetadataInterface $customerMetadata,
        AddressFactory $addressFactory
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
        $this->fbeHelper = $fbeHelper;
        $this->customerMetadata = $customerMetadata;
        $this->addressFactory = $addressFactory;
    }

    /**
     * Filters user data according to AAM settings and normalizes the fields
     *
     * Reads user data from session when no user data was passed
     * Customer parameter only used if $userDataArray is null
     *
     * @param Customer|null $customer
     * @param array|null $userDataArray
     * @return array|null
     */
    public function getNormalizedUserData($customer = null, $userDataArray = null): ?array
    {
        if (!$userDataArray) {
            try {
                $userDataArray = $this->getUserDataFromSession($customer);
            } catch (\Exception $e) {
                $this->fbeHelper->log(json_encode($e));
            }
        }

        $aamSettings = $this->fbeHelper->getAAMSettings();

        if (!$userDataArray || !$aamSettings || !$aamSettings->getEnableAutomaticMatching()) {
            return null;
        }

        //Removing fields not enabled in AAM settings
        foreach (array_keys($userDataArray) as $key) {
            if (!in_array($key, $aamSettings->getEnabledAutomaticMatchingFields())) {
                unset($userDataArray[$key]);
            }
        }

        // Normalizing gender and date of birth
        // According to https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
        $userDataArray = $this->normalizeGender($userDataArray);
        $userDataArray = $this->normalizeBirth($userDataArray);

        return $this->normalizeMatchingFields($userDataArray);
    }

    /**
     * Normalizing gender
     *
     * According to https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
     *
     * @param array $userDataArray
     * @return array
     */
    private function normalizeGender(array $userDataArray): array
    {
        if (array_key_exists(AAMSettingsFields::GENDER, $userDataArray)
            && !empty($userDataArray[AAMSettingsFields::GENDER])
        ) {
            $userDataArray[AAMSettingsFields::GENDER] = $userDataArray[AAMSettingsFields::GENDER][0];
        }

        return $userDataArray;
    }

    /**
     * Normalizing date of birth
     *
     * According to https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
     *
     * @param array $userDataArray
     * @return array
     */
    private function normalizeBirth(array $userDataArray): array
    {
        if (array_key_exists(AAMSettingsFields::DATE_OF_BIRTH, $userDataArray)
        ) {
            // strtotime() and date() return false for invalid parameters
            $unixTimestamp = strtotime($userDataArray[AAMSettingsFields::DATE_OF_BIRTH]);
            if (!$unixTimestamp) {
                unset($userDataArray[AAMSettingsFields::DATE_OF_BIRTH]);
            } else {
                $formattedDate = date("Ymd", $unixTimestamp);
                if (!$formattedDate) {
                    unset($userDataArray[AAMSettingsFields::DATE_OF_BIRTH]);
                } else {
                    $userDataArray[AAMSettingsFields::DATE_OF_BIRTH] = $formattedDate;
                }
            }
        }
        return $userDataArray;
    }

    /**
     * Given that the format of advanced matching fields is the same in
     * the Pixel and the Conversions API,
     * we can use the business sdk for normalization
     * Compare the documentation:
     * https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
     * https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
     *
     * @param array $userDataArray
     * @return array
     */
    private function normalizeMatchingFields(array $userDataArray): array
    {
        foreach ($userDataArray as $field => $data) {
            try {
                $normalizedValue = Normalizer::normalize($field, $data);
                $userDataArray[$field] = $normalizedValue;
            } catch (\Exception $e) {
                unset($userDataArray[$field]);
            }
        }

        return $userDataArray;
    }

    /**
     * Set user data
     *
     * @param Event $event
     * @param array $userDataArray
     * @return mixed
     * @SuppressWarnings(PHPMD)
     * Unable to refactor because UserData object from Facebook SDK does not have generic setter function
     */
    public function setUserData($event, $userDataArray = null)
    {
        $userDataArray = $this->getNormalizedUserData(null, $userDataArray);

        if (empty($userDataArray)) {
            return $event;
        }

        $userData = $event->getUserData();
        if (array_key_exists(AAMSettingsFields::EMAIL, $userDataArray)
        ) {
            $userData->setEmail(
                $userDataArray[AAMSettingsFields::EMAIL]
            );
        }
        if (array_key_exists(AAMSettingsFields::FIRST_NAME, $userDataArray)
        ) {
            $userData->setFirstName(
                $userDataArray[AAMSettingsFields::FIRST_NAME]
            );
        }
        if (array_key_exists(AAMSettingsFields::LAST_NAME, $userDataArray)
        ) {
            $userData->setLastName(
                $userDataArray[AAMSettingsFields::LAST_NAME]
            );
        }
        if (array_key_exists(AAMSettingsFields::GENDER, $userDataArray)
        ) {
            $userData->setGender(
                $userDataArray[AAMSettingsFields::GENDER]
            );
        }
        if (array_key_exists(AAMSettingsFields::DATE_OF_BIRTH, $userDataArray)
        ) {
            $userData->setDateOfBirth($userDataArray[AAMSettingsFields::DATE_OF_BIRTH]);
        }
        if (array_key_exists(AAMSettingsFields::EXTERNAL_ID, $userDataArray)
        ) {
            $userData->setExternalId(
                Util::hash($userDataArray[AAMSettingsFields::EXTERNAL_ID])
            );
        }
        if (array_key_exists(AAMSettingsFields::PHONE, $userDataArray)
        ) {
            $userData->setPhone(
                $userDataArray[AAMSettingsFields::PHONE]
            );
        }
        if (array_key_exists(AAMSettingsFields::CITY, $userDataArray)
        ) {
            $userData->setCity(
                $userDataArray[AAMSettingsFields::CITY]
            );
        }
        if (array_key_exists(AAMSettingsFields::STATE, $userDataArray)
        ) {
            $userData->setState(
                $userDataArray[AAMSettingsFields::STATE]
            );
        }
        if (array_key_exists(AAMSettingsFields::ZIP_CODE, $userDataArray)
        ) {
            $userData->setZipCode(
                $userDataArray[AAMSettingsFields::ZIP_CODE]
            );
        }
        if (array_key_exists(AAMSettingsFields::COUNTRY, $userDataArray)
        ) {
            $userData->setCountryCode(
                $userDataArray[AAMSettingsFields::COUNTRY]
            );
        }
        return $event;
    }

    /**
     * Return all of the match keys that can be extracted from user session
     *
     * @param Customer|null $customer
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getUserDataFromSession($customer): array
    {
        if (!$customer) {
            return [];
        }

        $userData = [];

        $userData[AAMSettingsFields::EXTERNAL_ID] = $customer->getId();
        $userData[AAMSettingsFields::EMAIL] = $this->magentoDataHelper->hashValue($customer->getEmail());
        $userData[AAMSettingsFields::FIRST_NAME] = $this->magentoDataHelper->hashValue($customer->getFirstname());
        $userData[AAMSettingsFields::LAST_NAME] = $this->magentoDataHelper->hashValue($customer->getLastname());
        $userData[AAMSettingsFields::DATE_OF_BIRTH] = $this->magentoDataHelper->hashValue($customer->getDob());
        if ($customer->getGender()) {
            $genderId = $customer->getGender();
            $userData[AAMSettingsFields::GENDER] =
                $this->magentoDataHelper->hashValue(
                    $this->customerMetadata->getAttributeMetadata('gender')
                        ->getOptions()[$genderId]->getLabel()
                );
        }

        $customerAddressId = $customer->getDefaultBilling();
        $billingAddress = $this->addressFactory->create()->load($customerAddressId);

        if ($billingAddress) {
            $userData[AAMSettingsFields::ZIP_CODE] =
                $this->magentoDataHelper->hashValue($billingAddress->getPostcode());
            $userData[AAMSettingsFields::CITY] =
                $this->magentoDataHelper->hashValue($billingAddress->getCity());
            $userData[AAMSettingsFields::PHONE] =
                $this->magentoDataHelper->hashValue($billingAddress->getTelephone());
            $userData[AAMSettingsFields::STATE] =
                $this->magentoDataHelper->hashValue($billingAddress->getRegionCode());
            $userData[AAMSettingsFields::COUNTRY] =
                $this->magentoDataHelper->hashValue($billingAddress->getCountryId());
        }

        return array_filter($userData);
    }
}
