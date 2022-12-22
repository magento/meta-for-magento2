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

namespace Meta\Conversion\Test\Unit\Block\Pixel;

use Meta\Conversion\Helper\AAMSettingsFields;

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
    protected function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->context = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->registry = $this->createMock(\Magento\Framework\Registry::class);
        $this->fbeHelper = $this->createMock(\Meta\BusinessExtension\Helper\FBEHelper::class);
        $this->magentoDataHelper = $this->createMock(\Meta\BusinessExtension\Helper\MagentoDataHelper::class);
        $this->aamFieldsExtractorHelper = $this->createMock(
            \Meta\Conversion\Helper\AAMFieldsExtractorHelper::class
        );

        $this->head =
        new \Meta\Conversion\Block\Pixel\Head(
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
