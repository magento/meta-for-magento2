<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use FacebookAds\Object\ServerSide\Normalizer;
use FacebookAds\Object\ServerSide\AdsPixelSettings;

use \Facebook\BusinessExtension\Helper\AAMSettingsFields;
use \Facebook\BusinessExtension\Helper\ServerEventFactory;
use PHPUnit\Framework\TestCase;

class AAMFieldsExtractorHelperTest extends TestCase
{

    protected $magentoDataHelper;

    protected $fbeHelper;

    protected $aamFieldsExtractorHelper;

  /**
   * Used to set the values before running a test
   *
   * @return void
   */
    public function setUp()
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->magentoDataHelper = $this->createMock(MagentoDataHelper::class);
        $this->aamFieldsExtractorHelper = new AAMFieldsExtractorHelper(
            $this->magentoDataHelper,
            $this->fbeHelper
        );
        $this->createDummyUserData();
    }

    public function createDummyUserData()
    {
        $userData1 = [
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
        ];
        $userData2 = [
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
        ];

        $this->magentoDataHelper->method('getUserDataFromSession')->willReturn($userData1);
        $this->magentoDataHelper->method('getUserDataFromOrder')->willReturn($userData2);
    }

    public function testUserDataArrayIsNullWhenAamNotFound()
    {
        $this->fbeHelper->method('getAAMSettings')->willReturn(null);
        $this->assertNull($this->aamFieldsExtractorHelper->getNormalizedUserData());
    }

    public function testUserDataArrayIsNullWhenAamDisabled()
    {
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(false);
        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
        $this->assertNull($this->aamFieldsExtractorHelper->getNormalizedUserData());
    }

    public function testReturnDataFromSessionWhenAAMEnabled()
    {
      // Enabling all aam fields
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $settings->setEnabledAutomaticMatchingFields(
            AAMSettingsFields::getAllFields()
        );

        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

        $userDataFromSession = $this->magentoDataHelper->getUserDataFromSession();

      // Getting the default user data
        $userData = $this->aamFieldsExtractorHelper->getNormalizedUserData();

        foreach (AAMSettingsFields::getAllFields() as $field) {
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

    public function testReturnUserDataFromArgumentWhenAAMEnabled()
    {
      // Enabling all aam fields
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $settings->setEnabledAutomaticMatchingFields(
            AAMSettingsFields::getAllFields()
        );

        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

        $userDataFromOrder = $this->magentoDataHelper->getUserDataFromOrder();
      // Passing an argument to normalize and filter
        $userData = $this->aamFieldsExtractorHelper->getNormalizedUserData($userDataFromOrder);

        foreach (AAMSettingsFields::getAllFields() as $field) {
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

    private function assertOnlyRequestedFieldsPresentInUserDataArray($fieldsSubset, $userDataArray)
    {
        $this->assertEquals(count($fieldsSubset), count($userDataArray));
        foreach ($fieldsSubset as $field) {
            $this->assertArrayHasKey($field, $userDataArray);
        }
    }

    private function createSubset($fields)
    {
        shuffle($fields);
        $randNum = rand()%count($fields);
        $subset = [];
        for ($i = 0; $i < $randNum; $i+=1) {
            $subset[] = $fields[$i];
        }
        return $subset;
    }

    public function testArrayWithRequestedUserDataWhenAamEnabled()
    {
        $possibleFields = AAMSettingsFields::getAllFields();
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
        for ($i = 0; $i<25; ++$i) {
            $fieldsSubset = $this->createSubset($possibleFields);
            $settings->setEnabledAutomaticMatchingFields($fieldsSubset);
            $userDataArray = $this->aamFieldsExtractorHelper->getNormalizedUserData();
            $this->assertOnlyRequestedFieldsPresentInUserDataArray($fieldsSubset, $userDataArray);
        }
    }

    public function testEventWithRequestedUserDataWhenAamEnabled()
    {
        $possibleFields = AAMSettingsFields::getAllFields();
        $settings = new AdsPixelSettings();
        $settings->setEnableAutomaticMatching(true);
        $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
        for ($i = 0; $i<25; ++$i) {
            $fieldsSubset = $this->createSubset($possibleFields);
            $settings->setEnabledAutomaticMatchingFields($fieldsSubset);
            $event = ServerEventFactory::createEvent('ViewContent', []);
            $event = $this->aamFieldsExtractorHelper->setUserData($event);
            $userData = $event->getUserData();
            $this->assertOnlyRequestedFieldsPresentInUserData($fieldsSubset, $userData);
        }
    }
}
