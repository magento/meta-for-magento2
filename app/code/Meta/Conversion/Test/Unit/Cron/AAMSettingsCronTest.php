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

namespace Meta\Conversion\Test\Unit\Cron;

use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Cron\AAMSettingsCron;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Store\Model\StoreManager;

/** Previously EventIdGeneratorTest */
class AAMSettingsCronTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    private $aamSettingsCron;

    /**
     * @var MockObject
     */
    private $fbeHelper;

    /**
     * @var MockObject
     */
    private $systemConfig;

    /**
     * @var MockObject
     */
    private $storeManager;

    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->storeManager = $this->createMock(StoreManager::class);
        $this->aamSettingsCron = new AAMSettingsCron($this->fbeHelper, $this->systemConfig, $this->storeManager);
        $this->storeManager->method('getStores')->willReturn([1 => ['store 1'], 2 => ['store 2']]);
    }

    /**
     * Test that return is false when there is no pixel in the db
     *
     * @return void
     */
    public function testFalseSettingsWhenNoPixelPresent()
    {
        $result = $this->aamSettingsCron->execute();

        $this->assertFalse($result);
    }

    /**
     * Test return is false when settings cannot be fetched
     *
     * @return void
     */
    public function testNullSettingsWhenAAMSettingsNotFetched()
    {
        $this->fbeHelper->method('fetchAndSaveAAMSettings')->willReturn(null);

        $result = $this->aamSettingsCron->execute();

        $this->assertFalse($result);
    }

    /**
     * Test that the settings returned by the cron object are not null when pixel and aam settings are valid
     *
     * @return void
     */
    public function testSettingsNotNullWhenPixelAndAAMSettingsAreValid()
    {
        $pixelId = '1234';
        $settingsAsArray = [
            "enableAutomaticMatching" => false,
            "enabledAutomaticMatchingFields" => ['em'],
            "pixelId" => $pixelId
        ];
        $settingsAsString = json_encode($settingsAsArray);
        $this->systemConfig->method('getPixelId')->willReturn($pixelId);
        $this->fbeHelper->method('fetchAndSaveAAMSettings')->with($pixelId)->willReturn($settingsAsString);

        $result = $this->aamSettingsCron->execute();

        $this->assertTrue($result);
    }
}
