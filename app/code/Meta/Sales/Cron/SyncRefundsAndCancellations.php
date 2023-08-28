<?php

declare(strict_types=1);

namespace Meta\Sales\Cron;

use Exception;
use Magento\Store\Model\StoreManagerInterface;
use Meta\Sales\Helper\CommerceHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\FBEHelper;

/**
 * Pulls pending refunds and cancellations from FB Commerce Account using FB Graph API
 */
class SyncRefundsAndCancellations
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SystemConfig $systemConfig
     * @param CommerceHelper $commerceHelper
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SystemConfig          $systemConfig,
        CommerceHelper        $commerceHelper,
        FBEHelper             $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->commerceHelper = $commerceHelper;
        $this->storeManager = $storeManager;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Sync refunds and cancellations from Facebook for a store
     *
     * @param int $storeId
     * @return void
     * @throws GuzzleException
     */
    private function pullRefundsAndCancellationsForStore(int $storeId)
    {
        if (!($this->systemConfig->isActiveExtension($storeId)
            && $this->systemConfig->isActiveOrderSync($storeId))) {
            return;
        }

        $this->commerceHelper->pullRefundOrders($storeId);
        $this->commerceHelper->pullCancelledOrders($storeId);
    }

    /**
     * Sync refunds and cancellations from Facebook for each Magento store
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $store) {
            try {
                $this->pullRefundsAndCancellationsForStore((int)$store->getId());
            } catch (Exception $e) {
                $context = [
                    'store_id' => $store->getId(),
                    'event' => 'refund_and_cancellation_sync',
                    'event_type' => 'sync_refunds_and_cancellations_cron',
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
            }
        }
    }
}
