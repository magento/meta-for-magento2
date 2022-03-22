<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use FacebookAds\Object\ServerSide\Normalizer;
use FacebookAds\Object\ServerSide\Util;

/**
 * Helper to extract and filter aam fields
 */
class AAMFieldsExtractorHelper
{
    /**
     * @var MagentoDataHelper
     */
    protected $magentoDataHelper;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * Constructor
     * @param MagentoDataHelper $magentoDataHelper
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper,
        FBEHelper $fbeHelper
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Filters user data according to AAM settings and normalizes the fields
     * Reads user data from session when no user data was passed
     * @param string[] $userDataArray
     * @return string[]
     */
    public function getNormalizedUserData($userDataArray = null)
    {
        if (!$userDataArray) {
            $userDataArray = $this->magentoDataHelper->getUserDataFromSession();
        }

        $aamSettings = $this->fbeHelper->getAAMSettings();

        if (!$userDataArray || !$aamSettings || !$aamSettings->getEnableAutomaticMatching()) {
            return null;
        }

        //Removing fields not enabled in AAM settings
        foreach ($userDataArray as $key => $value) {
            if (!in_array($key, $aamSettings->getEnabledAutomaticMatchingFields())) {
                unset($userDataArray[$key]);
            }
        }

        // Normalizing gender and date of birth
        // According to https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
        if (array_key_exists(AAMSettingsFields::GENDER, $userDataArray)
            && !empty($userDataArray[AAMSettingsFields::GENDER])
        ) {
            $userDataArray[AAMSettingsFields::GENDER] = $userDataArray[AAMSettingsFields::GENDER][0];
        }
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
        // Given that the format of advanced matching fields is the same in
        // the Pixel and the Conversions API,
        // we can use the business sdk for normalization
        // Compare the documentation:
        // https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
        // https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
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
     * @param $event
     * @param null $userDataArray
     * @return mixed
     */
    public function setUserData($event, $userDataArray = null)
    {
        $userDataArray = self::getNormalizedUserData($userDataArray);

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
}
