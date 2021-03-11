<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

class AAMSettingsCron
{
    /**
     * @var \Facebook\BusinessExtension\Helper\FBEHelper
     */
    protected $fbeHelper;

    /**
     * AAMSettingsCron constructor
     *
     * @param \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper
     */
    public function __construct(
        \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper
    ) {
        $this->fbeHelper = $fbeHelper;
    }

    public function execute()
    {
        $pixelId = $this->fbeHelper->getPixelID();
        $this->fbeHelper->log('In CronJob for fetching AAM Settings for Pixel: ' . $pixelId);
        $settingsAsString = null;
        if ($pixelId) {
            $settingsAsString = $this->fbeHelper->fetchAndSaveAAMSettings($pixelId);
            if ($settingsAsString) {
                $this->fbeHelper->log('Saving settings '.$settingsAsString);
            } else {
                $this->fbeHelper->log('Error saving settings');
            }
        }
        return $settingsAsString;
    }
}
