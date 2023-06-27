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

namespace Meta\Conversion\Test\Unit\Helper;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Meta\Conversion\Helper\AAMSettingsFields;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Magento\Customer\Api\CustomerMetadataInterface;
use FacebookAds\Object\ServerSide\AdsPixelSettings;
use FacebookAds\Object\ServerSide\Normalizer;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\AddressFactory;

class AAMFieldsExtractorHelperTest extends TestCase
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
     * @var AAMFieldsExtractorHelper
     */
    private AAMFieldsExtractorHelper $aamFieldsExtractorHelper;

    /**
     * @var CustomerMetadataInterface
     */
    private CustomerMetadataInterface $customerMetadata;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var AddressFactory
     */
    private AddressFactory $addressFactory;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->magentoDataHelper = $this->createMock(MagentoDataHelper::class);
        $this->customerMetadata = $this->createMock(CustomerMetadataInterface::class);
        $this->customerSession = $this->createMock(CustomerSession::class);
        $this->addressFactory = $this->createMock(AddressFactory::class);
        $this->aamFieldsExtractorHelper = new AAMFieldsExtractorHelper(
            $this->magentoDataHelper,
            $this->fbeHelper,
            $this->customerMetadata,
            $this->addressFactory
        );
    }

    public function testUserDataArrayIsNullWhenAamNotFound()
    {
        $this->fbeHelper->method('getAAMSettings')->willReturn(null);
        $this->assertNull($this->aamFieldsExtractorHelper->getNormalizedUserData($this->getCustomer()));
    }

    public function testUserDataArrayIsNullWhenAamDisabled()
    {
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(false);
        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
        $this->assertNull($this->aamFieldsExtractorHelper->getNormalizedUserData($this->getCustomer()));
    }

    /**
     * @dataProvider userDataFromSessionDataProvider
     * @param array $userDataFromSession
     * @return void
     */
    public function testReturnDataFromSessionWhenAAMEnabled(array $userDataFromSession): void
    {
        // Enabling all aam fields
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $settings->setEnabledAutomaticMatchingFields(
            AAMSettingsFields::ALL_FIELDS
        );

        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

        // Getting the default user data
        $userData = $this->aamFieldsExtractorHelper->getNormalizedUserData(null, $userDataFromSession);

        foreach (AAMSettingsFields::ALL_FIELDS as $field) {
            $this->assertArrayHasKey($field, $userData);
            $expectedValue = $userDataFromSession[$field];
            if ($field == AAMSettingsFields::GENDER) {
                $expectedValue = $expectedValue[0];
            } elseif ($field == AAMSettingsFields::DATE_OF_BIRTH) {
                $expectedValue = date("Ymd", strtotime($expectedValue));
            }
            $expectedValue = Normalizer::normalize($field, $expectedValue);

            $this->assertEquals($expectedValue, $userData[$field]);
        }
    }

    /**
     * @dataProvider userDataFromOrderDataProvider
     * @param array $userDataFromOrder
     * @return void
     */
    public function testReturnUserDataFromArgumentWhenAAMEnabled(array $userDataFromOrder)
    {
        // Enabling all aam fields
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $settings->setEnabledAutomaticMatchingFields(
            AAMSettingsFields::ALL_FIELDS
        );

        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

        // Passing an argument to normalize and filter
        $userData = $this->aamFieldsExtractorHelper->getNormalizedUserData($this->getCustomer(), $userDataFromOrder);

        foreach (AAMSettingsFields::ALL_FIELDS as $field) {
            $this->assertArrayHasKey($field, $userData);
            $expectedValue = $userDataFromOrder[$field];
            if ($field == AAMSettingsFields::GENDER) {
                $expectedValue = $expectedValue[0];
            } elseif ($field == AAMSettingsFields::DATE_OF_BIRTH) {
                $expectedValue = date("Ymd", strtotime($expectedValue));
            }
            $expectedValue = Normalizer::normalize($field, $expectedValue);

            $this->assertEquals($expectedValue, $userData[$field]);
        }
    }

    /**
     * @param $fieldsSubset
     * @param $userData
     * @return void
     * @SuppressWarnings(PHPMD)
     * Unable to refactor because UserData object from Facebook SDK does not have generic setter function
     */
    private function assertOnlyRequestedFieldsPresentInUserData($fieldsSubset, $userData)
    {
        $fieldsPresent = [];
        if ($userData->getLastName()) {
            $fieldsPresent[] = AAMSettingsFields::LAST_NAME;
        }
        if ($userData->getFirstName()) {
            $fieldsPresent[] = AAMSettingsFields::FIRST_NAME;
        }
        if ($userData->getEmail()) {
            $fieldsPresent[] = AAMSettingsFields::EMAIL;
        }
        if ($userData->getPhone()) {
            $fieldsPresent[] = AAMSettingsFields::PHONE;
        }
        if ($userData->getGender()) {
            $fieldsPresent[] = AAMSettingsFields::GENDER;
        }
        if ($userData->getCountryCode()) {
            $fieldsPresent[] = AAMSettingsFields::COUNTRY;
        }
        if ($userData->getZipCode()) {
            $fieldsPresent[] = AAMSettingsFields::ZIP_CODE;
        }
        if ($userData->getCity()) {
            $fieldsPresent[] = AAMSettingsFields::CITY;
        }
        if ($userData->getDateOfBirth()) {
            $fieldsPresent[] = AAMSettingsFields::DATE_OF_BIRTH;
        }
        if ($userData->getState()) {
            $fieldsPresent[] = AAMSettingsFields::STATE;
        }
        if ($userData->getExternalId()) {
            $fieldsPresent[] = AAMSettingsFields::EXTERNAL_ID;
        }
        sort($fieldsPresent);
        sort($fieldsSubset);
        $this->assertEquals($fieldsSubset, $fieldsPresent);
    }

    /**
     * Assert only requested fields present in user data array
     *
     * @param array $fieldsSubset
     * @param array $userDataArray
     * @return void
     */
    private function assertOnlyRequestedFieldsPresentInUserDataArray(array $fieldsSubset, array $userDataArray)
    {
        $this->assertEquals(count($fieldsSubset), count($userDataArray));
        foreach ($fieldsSubset as $field) {
            $this->assertArrayHasKey($field, $userDataArray);
        }
    }

    /**
     * Create a random subset of the list of fields provided as parameter
     *
     * @param array $fields
     * @return array
     */
    private function createSubset(array $fields): array
    {
        shuffle($fields);
        $randNum = rand() % count($fields);
        $subset = [];
        for ($i = 0; $i < $randNum; ++$i) {
            $subset[] = $fields[$i];
        }
        return $subset;
    }

    /**
     * Test array with requested user data when AAM enabled
     *
     * @dataProvider userDataFromSessionDataProvider
     * @param array $userDataFromSession
     * @return void
     */
    public function testArrayWithRequestedUserDataWhenAamEnabled(array $userDataFromSession)
    {
        $possibleFields = AAMSettingsFields::ALL_FIELDS;
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
        for ($i = 0; $i < 25; ++$i) {
            $fieldsSubset = $this->createSubset($possibleFields);
            $settings->setEnabledAutomaticMatchingFields($fieldsSubset);
            $userDataArray = $this->aamFieldsExtractorHelper->getNormalizedUserData(
                $this->getCustomer(),
                $userDataFromSession
            );
            $this->assertOnlyRequestedFieldsPresentInUserDataArray($fieldsSubset, $userDataArray);
        }
    }

    /**
     * Test event with requested user data when AAM enabled
     *
     * @dataProvider userDataFromOrderDataProvider
     * @param array $userDataFromOrder
     * @return void
     */
    public function testEventWithRequestedUserDataWhenAamEnabled(array $userDataFromOrder): void
    {
        $possibleFields = AAMSettingsFields::ALL_FIELDS;
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
        for ($i = 0; $i < 25; ++$i) {
            $fieldsSubset = $this->createSubset($possibleFields);
            $settings->setEnabledAutomaticMatchingFields($fieldsSubset);
            $httpRequestMock = $this->createMock(Http::class);
            $httpRequestMock->method('getServerValue')->willReturn([]);
            $serverEventFactory = new ServerEventFactory($httpRequestMock, []);
            $event = $serverEventFactory->createEvent('ViewContent', []);
            $event = $this->aamFieldsExtractorHelper->setUserData($event, $userDataFromOrder);
            $userData = $event->getUserData();
            $this->assertOnlyRequestedFieldsPresentInUserData($fieldsSubset, $userData);
        }
    }

    /**
     * Get logged in customer
     *
     * @return Customer|null
     */
    private function getCustomer(): ?Customer
    {
        if (!$this->customerSession->isLoggedIn()) {
            return null;
        }

        return $this->customerSession->getCustomer();
    }

    /**
     * User data from session data provider
     *
     * @return array
     */
    public function userDataFromSessionDataProvider(): array
    {
        return [
            [
                'user_data' => [
                    AAMSettingsFields::EMAIL => 'abc@mail.com',
                    AAMSettingsFields::LAST_NAME => 'Perez',
                    AAMSettingsFields::FIRST_NAME => 'Pedro',
                    AAMSettingsFields::PHONE => '567891234',
                    AAMSettingsFields::GENDER => 'Male',
                    AAMSettingsFields::EXTERNAL_ID => '1',
                    AAMSettingsFields::COUNTRY => 'US',
                    AAMSettingsFields::CITY => 'Seattle',
                    AAMSettingsFields::STATE => 'WA',
                    AAMSettingsFields::ZIP_CODE => '12345',
                    AAMSettingsFields::DATE_OF_BIRTH => '1990-06-11',

                ]
            ]
        ];
    }

    /**
     * User data from order data provider
     *
     * @return array
     */
    public function userDataFromOrderDataProvider(): array
    {
        return [
            [
                'user_data' => [
                    AAMSettingsFields::EMAIL => 'def@mail.com',
                    AAMSettingsFields::LAST_NAME => 'Homer',
                    AAMSettingsFields::FIRST_NAME => 'Simpson',
                    AAMSettingsFields::PHONE => '12345678',
                    AAMSettingsFields::GENDER => 'Male',
                    AAMSettingsFields::EXTERNAL_ID => '2',
                    AAMSettingsFields::COUNTRY => 'US',
                    AAMSettingsFields::CITY => 'Springfield',
                    AAMSettingsFields::STATE => 'OH',
                    AAMSettingsFields::ZIP_CODE => '12345',
                    AAMSettingsFields::DATE_OF_BIRTH => '1982-06-11',
                ]
            ]
        ];
    }
}
