<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use \Facebook\BusinessExtension\Helper\ServerEventFactory;
use \Facebook\BusinessExtension\Helper\AAMSettingsFields;

use \FacebookAds\Object\ServerSide\AdsPixelSettings;
use \FacebookAds\Object\ServerSide\Util;

class ServerSideHelperTest extends \PHPUnit\Framework\TestCase{

  protected $magentoDataHelper;

  protected $fbeHelper;

  protected $serverSideHelper;

  protected $objectManager;

  /**
    * Used to reset or change values after running a test
    *
    * @return void
  */
  public function tearDown() {
  }

  /**
    * Used to set the values before running a test
    *
    * @return void
  */
  public function setUp() {
    $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
    $this->magentoDataHelper = $this->createMock(\Facebook\BusinessExtension\Helper\MagentoDataHelper::class);
    $this->serverSideHelper = new \Facebook\BusinessExtension\Helper\ServerSideHelper($this->fbeHelper, $this->magentoDataHelper);
    $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    $this->fbeHelper->method('getAccessToken')->willReturn('abc');
    $this->fbeHelper->method('getPixelID')->willReturn('123');
    $this->createDummyUserData();
  }

  public function createDummyUserData(){
    $userData1 = array(
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
      AAMSettingsFields::DATE_OF_BIRTH => '2010-06-11',
    );
    $userData2 = array(
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
    );

    $this->magentoDataHelper->method('getUserDataFromSession')->willReturn($userData1);
    $this->magentoDataHelper->method('getUserDataFromOrder')->willReturn($userData2);
  }

  private function assertUserDataNull($userData){
    $this->assertNull($userData->getEmail());
    $this->assertNull($userData->getGender());
    $this->assertNull($userData->getFirstName());
    $this->assertNull($userData->getLastName());
    $this->assertNull($userData->getDateOfBirth());
    $this->assertNull($userData->getExternalId());

    $this->assertNull($userData->getCity());
    $this->assertNull($userData->getZipCode());
    $this->assertNull($userData->getCountryCode());
    $this->assertNull($userData->getState());
    $this->assertNull($userData->getPhone());
  }

  public function testEventWithoutUserDataWhenAamSettingsNotFound(){
    $this->fbeHelper->method('getAAMSettings')->willReturn(null);

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertUserDataNull($event->getUserData());
  }

  public function testEventWithoutUserDataWhenAamDisabled(){
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(false);
    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertUserDataNull($event->getUserData());
  }

  public function assertEqualUserData($userData, $userDataArray){
    $this->assertEquals($userData->getEmail(), $userDataArray[AAMSettingsFields::EMAIL]);
    $this->assertEquals($userData->getFirstName(), $userDataArray[AAMSettingsFields::FIRST_NAME]);
    $this->assertEquals($userData->getLastName(), $userDataArray[AAMSettingsFields::LAST_NAME]);
    $this->assertEquals($userData->getDateOfBirth(), date("Ymd", strtotime($userDataArray[AAMSettingsFields::DATE_OF_BIRTH])));
    $this->assertEquals($userData->getGender(), $userDataArray[AAMSettingsFields::GENDER][0]);
    $this->assertEquals($userData->getExternalId(), Util::hash($userDataArray[AAMSettingsFields::EXTERNAL_ID]));
    $this->assertEquals($userData->getCity(), $userDataArray[AAMSettingsFields::CITY]);
    $this->assertEquals($userData->getZipCode(), $userDataArray[AAMSettingsFields::ZIP_CODE]);
    $this->assertEquals($userData->getCountryCode(), $userDataArray[AAMSettingsFields::COUNTRY]);
    $this->assertEquals($userData->getPhone(), $userDataArray[AAMSettingsFields::PHONE]);
    $this->assertEquals($userData->getState(), $userDataArray[AAMSettingsFields::STATE]);
  }

  public function testEventWithUserDataWhenAamEnabled(){
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(true);
    $settings->setEnabledAutomaticMatchingFields(
      AAMSettingsFields::getAllFields()
    );

    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];
    $userData = $event->getUserData();

    $userDataFromSession = $this->magentoDataHelper->getUserDataFromSession();

    $this->assertEqualUserData($userData, $userDataFromSession);
  }

  public function testEventWithPassedUserDataWhenAamEnabled(){
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(true);
    $settings->setEnabledAutomaticMatchingFields(
      AAMSettingsFields::getAllFields()
    );

    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

    $userDataFromOrder = $this->magentoDataHelper->getUserDataFromOrder();

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event, $userDataFromOrder);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];
    $userData = $event->getUserData();

    $this->assertEqualUserData($userData, $userDataFromOrder);
  }


  private function createSubset($fields){
    shuffle($fields);
    $randNum = rand()%count($fields);
    $subset = array();
    for( $i = 0; $i < $randNum; $i+=1 ){
      $subset[] = $fields[$i];
    }
    return $subset;
  }

  private function assertOnlyRequestedFieldsPresent($fieldsSubset, $userData){
    $fieldsPresent = array();
    if($userData->getLastName()){
      $fieldsPresent[] = AAMSettingsFields::LAST_NAME;
    }
    if($userData->getFirstName()){
      $fieldsPresent[] = AAMSettingsFields::FIRST_NAME;
    }
    if($userData->getEmail()){
      $fieldsPresent[] = AAMSettingsFields::EMAIL;
    }
    if($userData->getPhone()){
      $fieldsPresent[] = AAMSettingsFields::PHONE;
    }
    if($userData->getGender()){
      $fieldsPresent[] = AAMSettingsFields::GENDER;
    }
    if($userData->getCountryCode()){
      $fieldsPresent[] = AAMSettingsFields::COUNTRY;
    }
    if($userData->getZipCode()){
      $fieldsPresent[] = AAMSettingsFields::ZIP_CODE;
    }
    if($userData->getCity()){
      $fieldsPresent[] = AAMSettingsFields::CITY;
    }
    if($userData->getDateOfBirth()){
      $fieldsPresent[] = AAMSettingsFields::DATE_OF_BIRTH;
    }
    if($userData->getState()){
      $fieldsPresent[] = AAMSettingsFields::STATE;
    }
    if($userData->getExternalId()){
      $fieldsPresent[] = AAMSettingsFields::EXTERNAL_ID;
    }
    sort($fieldsPresent);
    sort($fieldsSubset);
    $this->assertEquals($fieldsSubset, $fieldsPresent);
  }

  public function testEventWithRequestedUserDataWhenAamEnabled(){
    $possibleFields = AAMSettingsFields::getAllFields();
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(true);
    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
    for( $i = 0; $i<50; $i += 1 ){
      $fieldsSubset = $this->createSubset($possibleFields);
      $settings->setEnabledAutomaticMatchingFields($fieldsSubset);
      $event = ServerEventFactory::createEvent('ViewContent', array());
      $this->serverSideHelper->sendEvent($event);
      $this->assertEquals($i + 1, count($this->serverSideHelper->getTrackedEvents()));
      $event = $this->serverSideHelper->getTrackedEvents()[$i];
      $userData = $event->getUserData();
      $this->assertOnlyRequestedFieldsPresent($fieldsSubset, $userData);
    }
  }
}
