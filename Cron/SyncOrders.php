<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Pulls pending orders from FB Commerce Account using FB Graph API
 */
class SyncOrders
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var CommerceHelper
     */
    private $commerceHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SystemConfig $systemConfig
     * @param CommerceHelper $commerceHelper
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfig $systemConfig, CommerceHelper $commerceHelper, LoggerInterface $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
        $this->logger = $logger;
    }

    /**
     * @param $storeId
     * @throws GuzzleException
     */
    protected function pullOrdersForStore($storeId)
    {
        if (!($this->systemConfig->isActiveExtension($storeId) && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }
        $this->commerceHelper->setStoreId($storeId)
            ->pullPendingOrders();
    }

    public function execute()
    {
        foreach ($this->systemConfig->getStoreManager()->getStores() as $store) {
            try {
                $this->pullOrdersForStore($store->getId());
            } catch (GuzzleException $e) {
                $this->logger->critical($e);
            }
        }
    }
}
