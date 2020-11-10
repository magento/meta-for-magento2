<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Util;
use FacebookAds\Exception\Exception;
use FacebookAds\Object\ServerSide\AdsPixelSettings;

/**
 * Helper to fire ServerSide Event.
 */
class ServerSideHelper {

  /**
   * @var \Facebook\BusinessExtension\Helper\FBEHelper
   */
  protected $_fbeHelper;

  /**
   * @var \Facebook\BusinessExtension\Helper\MagentoDataHelper
   */
  protected $_magentoDataHelper;

  /**
   * @var array FacebookAds\Object\ServerSide\Event
  */
  protected $trackedEvents;

   /**
   * Constructor
   * @param \Facebook\BusinessExtension\Helper\FBEHelper $helper
   * @param \Facebook\BusinessExtension\Helper\MagentoDataHelper $helper
   */
  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper
    ) {
    $this->_fbeHelper = $fbeHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
    $this->trackedEvents = array();
  }

  public function setUserData( $event, $userDataArray ){
    if(!$userDataArray){
      return $event;
    }

    $aamSettings = $this->_fbeHelper->getAAMSettings();

    if( !$aamSettings || !$aamSettings->getEnableAutomaticMatching() ){
      return $event;
    }

    //Removing fields not enabled in AAM settings
    foreach ($userDataArray as $key => $value) {
      if(!in_array($key, $aamSettings->getEnabledAutomaticMatchingFields())){
        unset($userDataArray[$key]);
      }
    }

    $userData = $event->getUserData();
    if(
      array_key_exists(AAMSettingsFields::EMAIL, $userDataArray)
    ){
      $userData->setEmail(
        $userDataArray[AAMSettingsFields::EMAIL]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::FIRST_NAME, $userDataArray)
    ){
      $userData->setFirstName(
        $userDataArray[AAMSettingsFields::FIRST_NAME]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::LAST_NAME, $userDataArray)
    ){
      $userData->setLastName(
        $userDataArray[AAMSettingsFields::LAST_NAME]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::GENDER, $userDataArray)
    ){
      $userData->setGender(
        $userDataArray[AAMSettingsFields::GENDER][0]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::DATE_OF_BIRTH, $userDataArray)
    ){
      $userData->setDateOfBirth(
        date("Ymd", strtotime($userDataArray[AAMSettingsFields::DATE_OF_BIRTH]))
      );
    }
    if(
      array_key_exists(AAMSettingsFields::EXTERNAL_ID, $userDataArray)
    ){
      $userData->setExternalId(
        Util::hash($userDataArray[AAMSettingsFields::EXTERNAL_ID])
      );
    }
    if(
      array_key_exists(AAMSettingsFields::PHONE, $userDataArray)
    ){
      $userData->setPhone(
        $userDataArray[AAMSettingsFields::PHONE]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::CITY, $userDataArray)
    ){
      $userData->setCity(
        $userDataArray[AAMSettingsFields::CITY]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::STATE, $userDataArray)
    ){
      $userData->setState(
        $userDataArray[AAMSettingsFields::STATE]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::ZIP_CODE, $userDataArray)
    ){
      $userData->setZipCode(
        $userDataArray[AAMSettingsFields::ZIP_CODE]
      );
    }
    if(
      array_key_exists(AAMSettingsFields::COUNTRY, $userDataArray)
    ){
      $userData->setCountryCode(
        $userDataArray[AAMSettingsFields::COUNTRY]
      );
    }
    return $event;
  }

  public function sendEvent($event, $userDataArray = null) {
    try
    {
      $api = Api::init(null, null, $this->_fbeHelper->getAccessToken());

      if(!$userDataArray){
        $userDataArray = $this->_magentoDataHelper->getUserDataFromSession();
      }

      $event = $this->setUserData($event, $userDataArray);

      $this->trackedEvents[] = $event;

      $events = array();
      array_push($events, $event);

      $request = (new EventRequest($this->_fbeHelper->getPixelID()))
          ->setEvents($events)
          ->setPartnerAgent($this->_fbeHelper->getPartnerAgent());

      $this->_fbeHelper->log('Sending event '.$event->getEventId());

      $response = $request->execute();

    } catch (Exception $e) {
      $this->_fbeHelper->log(json_encode($e));
    }
  }

  public function getTrackedEvents(){
    return $this->trackedEvents;
  }
}
