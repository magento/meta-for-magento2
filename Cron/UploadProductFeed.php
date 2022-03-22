<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Model\Product\Feed\Uploader;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

use Magento\Framework\Exception\LocalizedException;
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
    public function __construct(SystemConfig $systemConfig, Uploader $uploader, LoggerInterface $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
        $this->logger = $logger;
    }

    /**
     * @param $storeId
     * @return $this
     * @throws LocalizedException
     */
    protected function uploadForStore($storeId)
    {
        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveDailyProductFeed($storeId))) {
            return $this;
        }
        $this->uploader->uploadFullCatalog($storeId);
        return $this;
    }

    public function execute()
    {
        foreach ($this->systemConfig->getStoreManager()->getStores() as $store) {
            try {
                $this->uploadForStore($store->getId());
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
