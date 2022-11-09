<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\FBEHelper;

class AAMSettingsCron
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * AAMSettingsCron constructor
     *
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        FBEHelper $fbeHelper
    ) {
        $this->fbeHelper = $fbeHelper;
    }

    public function execute()
    {
        $pixelId = $this->fbeHelper->getPixelID();
        $settingsAsString = null;
        if ($pixelId) {
            $settingsAsString = $this->fbeHelper->fetchAndSaveAAMSettings($pixelId);
            if (!$settingsAsString) {
                $this->fbeHelper->log('Error saving settings. Currently:', $settingsAsString);
            }
        }
        return $settingsAsString;
    }
}
