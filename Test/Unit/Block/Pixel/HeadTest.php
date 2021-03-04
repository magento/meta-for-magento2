<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Block\Pixel;

use \Facebook\BusinessExtension\Helper\AAMSettingsFields;

class HeadTest extends \PHPUnit\Framework\TestCase
{

    protected $head;

    protected $context;

    protected $objectManager;

    protected $registry;

    protected $fbeHelper;

    protected $magentoDataHelper;

    protected $aamFieldsExtractorHelper;

  /**
   * Used to reset or change values after running a test
   *
   * @return void
   */
    public function tearDown()
    {
    }

  /**
   * Used to set the values before running a test
   *
   * @return void
   */
    public function setUp()
    {

        $this->context = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->registry = $this->createMock(\Magento\Framework\Registry::class);
        $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
        $this->magentoDataHelper = $this->createMock(\Facebook\BusinessExtension\Helper\MagentoDataHelper::class);
        $this->aamFieldsExtractorHelper = $this->createMock(
            \Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper::class
        );

        $this->head =
        new \Facebook\BusinessExtension\Block\Pixel\Head(
            $this->context,
            $this->objectManager,
            $this->registry,
            $this->fbeHelper,
            $this->magentoDataHelper,
            $this->aamFieldsExtractorHelper,
            []
        );
    }

  /**
   * Test if the json string returned by the Head block
   * is empty when the user is not logged in
   *
   * @return void
   */
    public function testReturnEmptyJsonStringWhenUserIsNotLoggedIn()
    {
        $this->aamFieldsExtractorHelper->method('getNormalizedUserData')
        ->willReturn(null);
        $jsonString = $this->head->getPixelInitCode();
        $this->assertEquals('{}', $jsonString);
    }

  /**
   * Test if the json string returned by the Head block
   * is not empty when the user is logged in
   *
   * @return void
   */
    public function testReturnNonEmptyJsonStringWhenUserIsLoggedIn()
    {
        $userDataArray = [
        AAMSettingsFields::EMAIL => 'def@mail.com',
        AAMSettingsFields::LAST_NAME => 'homer',
        AAMSettingsFields::FIRST_NAME => 'simpson',
        AAMSettingsFields::PHONE => '12345678',
        AAMSettingsFields::GENDER => 'm',
        AAMSettingsFields::EXTERNAL_ID => '2',
        AAMSettingsFields::COUNTRY => 'us',
        AAMSettingsFields::CITY => 'springfield',
        AAMSettingsFields::STATE => 'oh',
        AAMSettingsFields::ZIP_CODE => '12345',
        AAMSettingsFields::DATE_OF_BIRTH => '19820611',
        ];
        $this->aamFieldsExtractorHelper->method('getNormalizedUserData')
        ->willReturn($userDataArray);
        $jsonString = $this->head->getPixelInitCode();
        $expectedJsonString = json_encode($userDataArray, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
        $this->assertEquals($expectedJsonString, $jsonString);
    }
}
