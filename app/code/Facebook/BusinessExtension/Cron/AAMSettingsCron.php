<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class AAMSettingsCron
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * AAMSettingsCron constructor
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
    }

    public function execute()
    {
        $pixelId = $this->systemConfig->getPixelId();
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
