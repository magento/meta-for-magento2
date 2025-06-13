<?php

declare(strict_types=1);

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

namespace Meta\Sales\Observer\Order;

use Exception;
use Meta\Sales\Helper\CommerceHelper;
use Meta\Sales\Model\Order\CreateRefund;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Observer\MetaObserverTrait;

class Refund implements ObserverInterface
{
    use MetaObserverTrait;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private FacebookOrderInterfaceFactory $facebookOrderFactory;

    /**
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param CommerceHelper $commerceHelper
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        SystemConfig                  $systemConfig,
        GraphAPIAdapter               $graphAPIAdapter,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        CommerceHelper                $commerceHelper,
        FBEHelper                     $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->commerceHelper = $commerceHelper;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Get Exception Event
     *
     * @return string
     */
    public function getExceptionEvent()
    {
        return 'refund_observer_exception';
    }

    /**
     * Get Store ID
     *
     * @param Observer $observer
     * @return string
     */
    public function getStoreId(Observer $observer)
    {
        return $observer->getEvent()->getPayment()->getOrder()->getStoreId();
    }

    /**
     * Get Facebook Event Helper
     *
     * @return FBEHelper
     */
    public function getFBEHelper(): FBEHelper
    {
        return $this->fbeHelper;
    }

    /**
     * Refund facebook order from observer event
     *
     * @param Observer $observer
     * @return void
     * @throws GuzzleException|Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function executeImpl(Observer $observer)
    {
        /**
         * @var Payment $payment
         */
        $payment = $observer->getEvent()->getPayment();
        /**
         * @var CreditmemoInterface $creditmemo
         */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $comments = $creditmemo->getComments();

        foreach ($comments as $comment) {
            $commentText = $comment->getComment();
            // You can now use $commentText, for example:
            if (CreateRefund::CREDIT_MEMO_NOTE === $commentText) {
                // This was a refund from Meta Commerce. No need to loop.
                return;
            }
        }

        $storeId = $payment->getOrder()->getStoreId();

        if (!($this->systemConfig->isOrderSyncEnabled($storeId)
            && $this->systemConfig->isActiveExtension($storeId))
        ) {
            return;
        }

        // @todo fix magento bug with incorrectly loading order in credit memo causing missing extension attributes
        // https://github.com/magento/magento2/issues/23345

        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($payment->getOrder()->getId(), 'magento_order_id');

        if (!$facebookOrder->getFacebookOrderId()) {
            return;
        }

        $deductionAmount = abs($creditmemo->getAdjustmentNegative());
        $adjustmentAmount = $creditmemo->getAdjustmentPositive();

        $shippingRefundAmount = $creditmemo->getBaseShippingAmount();
        $reasonText = $creditmemo->getCustomerNote();
        $currencyCode = $payment->getOrder()->getOrderCurrencyCode();

        // refunds in the UK are after tax
        if ($currencyCode === 'GBP') {
            $shippingRefundAmount += $creditmemo->getShippingTaxAmount();
        }

        try {
            $refundItemsBySku = $this->getRefundItems($creditmemo, $payment, false);
            $this->refundOrder(
                (int)$storeId,
                $facebookOrder->getFacebookOrderId(),
                $refundItemsBySku,
                $shippingRefundAmount,
                $deductionAmount,
                $adjustmentAmount,
                $currencyCode,
                $reasonText
            );
        } catch (LocalizedException $e) {
            $refundItemsByID = $this->getRefundItems($creditmemo, $payment, true);
            $this->refundOrder(
                (int)$storeId,
                $facebookOrder->getFacebookOrderId(),
                $refundItemsByID,
                $shippingRefundAmount,
                $deductionAmount,
                $adjustmentAmount,
                $currencyCode,
                $reasonText
            );
        }
        $payment->getOrder()->addCommentToStatusHistory('Order Refunded on Meta');
    }

    /**
     * Refund a facebook order
     *
     * @param int $storeId
     * @param string $fbOrderId
     * @param array $items
     * @param float|null $shippingRefundAmount
     * @param float|null $deductionAmount
     * @param float|null $adjustmentAmount
     * @param string|null $currencyCode
     * @param string|null $reasonText
     * @throws GuzzleException
     * @throws Exception
     */
    private function refundOrder(
        int     $storeId,
        string  $fbOrderId,
        array   $items,
        ?float  $shippingRefundAmount,
        ?float  $deductionAmount,
        ?float  $adjustmentAmount,
        ?string $currencyCode,
        ?string $reasonText = null
    ) {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        try {
            $this->graphAPIAdapter->refundOrder(
                $fbOrderId,
                $items,
                $shippingRefundAmount,
                $deductionAmount,
                $adjustmentAmount,
                $currencyCode,
                $reasonText
            );
        } catch (GuzzleException $e) {
            $response = $e->getResponse();
            $body = json_decode((string)$response->getBody());
            throw new LocalizedException(
                __(
                    'Error code: "%1"; Error message: "%2"',
                    (string)$body->error->code,
                    (string)($body->error->error_user_msg ?? $body->error->message)
                )
            );
        }
    }

    /**
     * Private helper function that returns array of items that should be refunded
     *
     * @param CreditmemoInterface $creditmemo
     * @param OrderPaymentInterface $payment
     * @param bool $useNumericID
     * @return array
     */
    private function getRefundItems(
        CreditmemoInterface   $creditmemo,
        OrderPaymentInterface $payment,
        bool                  $useNumericID
    ): array {
        $refundItems = [];

        foreach ($creditmemo->getItems() as $item) {
            if ($item->getQty() > 0) {
                $item_id = $useNumericID ? $item->getProductId() : $item->getSku();
                if ($item->getDiscountAmount() == 0) {
                    $refundItems[] = [
                        'retailer_id' => $item_id,
                        'item_refund_quantity' => $item->getQty(),
                    ];
                } else {
                    // @todo refunds by qty for items with discount is unavailable atm;
                    //     once it is available the else statement should be removed
                    $refundItems[] = [
                        'retailer_id' => $item_id,
                        'item_refund_amount' => [
                            'amount' => $item->getRowTotal() - $item->getDiscountAmount(),
                            'currency' => $payment->getOrder()->getOrderCurrencyCode()
                        ],
                    ];
                }
            }
        }

        return $refundItems;
    }

    /**
     * Private helper function that returns array of items that should be canceled
     *
     * @param CreditmemoInterface $creditmemo
     * @param bool $useNumericID
     * @return array
     */
    private function getCanceledItems(
        CreditmemoInterface $creditmemo,
        bool                $useNumericID
    ): array {
        $canceledItems = [];

        foreach ($creditmemo->getItems() as $item) {
            if ($item->getQty() > 0) {
                $item_id = $useNumericID ? $item->getProductId() : $item->getSku();
                $canceledItems[] = [
                    'retailer_id' => $item_id,
                    'quantity' => $item->getQty(),
                ];
            }
        }

        return $canceledItems;
    }
}
