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

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config;
use Meta\Conversion\Block\Pixel\Head;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Meta\Conversion\Helper\AAMSettingsFields;
use Meta\Conversion\Helper\MagentoDataHelper;
use PHPUnit\Framework\TestCase;

class HeadTest extends TestCase
{
    /**
     * @var Head
     */
    private Head $head;

    /**
     * @var Context
     */
    private Context $context;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var AAMFieldsExtractorHelper
     */
    private AAMFieldsExtractorHelper $aamFieldsExtractorHelper;

    /**
     * @var Config
     */
    private Config $systemConfig;

    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->magentoDataHelper = $this->createMock(MagentoDataHelper::class);
        $this->systemConfig = $this->createMock(Config::class);
        $this->escaper = $this->createMock(Escaper::class);
        $this->customerSession = $this->createMock(CustomerSession::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->aamFieldsExtractorHelper = $this->createMock(AAMFieldsExtractorHelper::class);
        $this->escaper = $this->createMock(Escaper::class);

        $this->head = new Head(
            $this->context,
            $this->fbeHelper,
            $this->magentoDataHelper,
            $this->systemConfig,
            $this->escaper,
            $this->checkoutSession,
            $this->aamFieldsExtractorHelper,
            $this->customerSession
        );
    }

    /**
     * Test if the json string returned by the Head block
     *
     * @return void
     */
    public function testReturnEmptyJsonStringWhenUserIsNotLoggedIn()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->aamFieldsExtractorHelper->method('getNormalizedUserData')
            ->with(null)
            ->willReturn(null);
        $jsonString = $this->head->getPixelInitCode();
        $this->assertEquals('{}', $jsonString);
    }

    /**
     * Test if the json string returned by the Head block
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
        $customerMock = $this->createMock(Customer::class);
        $this->customerSession->method('getCustomer')->willReturn($customerMock);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $this->aamFieldsExtractorHelper->method('getNormalizedUserData')
            ->with($customerMock)
            ->willReturn($userDataArray);
        $jsonString = $this->head->getPixelInitCode();
        $expectedJsonString = json_encode($userDataArray, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
        $this->assertEquals($expectedJsonString, $jsonString);
    }
}
