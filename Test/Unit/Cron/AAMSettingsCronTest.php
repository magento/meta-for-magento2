<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Cron;

/** Previously EventIdGeneratorTest */
class AAMSettingsCronTest extends \PHPUnit\Framework\TestCase
{
    protected $aamSettingsCron;

    protected $fbeHelper;

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
        $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
        $this->aamSettingsCron = new \Facebook\BusinessExtension\Cron\AAMSettingsCron($this->fbeHelper);
    }

    /**
     * Test that the settings returned by the cron object are null when there is no pixel in the db
     *
     * @return void
     */
    public function testNullSettingsWhenNoPixelPresent()
    {
        $result = $this->aamSettingsCron->execute();

        $this->assertNull($result);
    }

    /**
     * Test that the settings returned by the cron object are null when they cannot be fetched
     *
     * @return void
     */
    public function testNullSettingsWhenAAMSettingsNotFetched()
    {
        $this->fbeHelper->method('fetchAndSaveAAMSettings')->willReturn(null);

        $result = $this->aamSettingsCron->execute();

        $this->assertNull($result);
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
        "enableAutomaticMatching"=>false,
        "enabledAutomaticMatchingFields"=>['em'],
        "pixelId"=>$pixelId
        ];
        $settingsAsString = json_encode($settingsAsArray);

        $this->fbeHelper->method('fetchAndSaveAAMSettings')->willReturn($settingsAsString);

        $result = $this->aamSettingsCron->execute();

        $this->assertEquals($settingsAsString, $result);
    }
}
