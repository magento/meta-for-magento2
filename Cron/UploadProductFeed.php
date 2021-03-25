<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Model\Product\Feed\Uploader;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

use Psr\Log\LoggerInterface;

class UploadProductFeed
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfig $systemConfig, Uploader $uploader, $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveDailyProductFeed())) {
            return;
        }

        try {
            $this->uploader->uploadFullCatalog();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
