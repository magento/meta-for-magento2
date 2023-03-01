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

namespace Meta\Sales\Observer\Order;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface as CreditmemoItem;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;

class Refund implements ObserverInterface
{
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
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphAPIAdapter,
        FacebookOrderInterfaceFactory $facebookOrderFactory
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->facebookOrderFactory = $facebookOrderFactory;
    }

    /**
     * Get retailer id
     *
     * @param CreditmemoItem $creditmemoItem
     * @return string|int|bool
     */
    protected function getRetailerId(CreditmemoItem $creditmemoItem)
    {
        if ($this->systemConfig->getProductIdentifierAttr() === IdentifierConfig::PRODUCT_IDENTIFIER_SKU) {
            return $creditmemoItem->getSku();
        } elseif ($this->systemConfig->getProductIdentifierAttr() === IdentifierConfig::PRODUCT_IDENTIFIER_ID) {
            return $creditmemoItem->getProductId();
        }
        return false;
    }

    /**
     * Refund facebook order from observer event
     *
     * @param Observer $observer
     * @return void
     * @throws GuzzleException|Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $storeId = $payment->getOrder()->getStoreId();

        if (!($this->systemConfig->isActiveExtension($storeId)
            && $this->systemConfig->isActiveOrderSync($storeId)
            && $this->systemConfig->isOnsiteCheckoutEnabled($storeId))) {
            return;
        }

        // @todo fix magento bug with incorrectly loading order in credit memo resulting in missing extension attributes
        // https://github.com/magento/magento2/issues/23345

        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($payment->getOrder()->getId(), 'magento_order_id');

        if (!$facebookOrder->getFacebookOrderId()) {
            return;
        }

        if ($creditmemo->getAdjustment() > 0) {
            throw new Exception('Cannot refund order on Facebook. Refunds with adjustments are not yet supported.');
        }

        $refundItems = $this->getRefundItems($creditmemo, $payment);

        $shippingRefundAmount = $creditmemo->getBaseShippingAmount();
        $reasonText = $creditmemo->getCustomerNote();
        $currencyCode = $payment->getOrder()->getOrderCurrencyCode();

        // refunds in the UK are after tax
        if ($currencyCode === 'GBP') {
            $shippingRefundAmount += $creditmemo->getShippingTaxAmount();
        }

        $this->refundOrder(
            (int)$storeId,
            $facebookOrder->getFacebookOrderId(),
            $refundItems,
            $shippingRefundAmount,
            $currencyCode,
            $reasonText
        );

        $payment->getOrder()->addCommentToStatusHistory('Refunded order on Facebook');
    }

    /**
     * Refund a facebook order
     *
     * @param int $storeId
     * @param string $fbOrderId
     * @param array $items
     * @param float|null $shippingRefundAmount
     * @param string|null $currencyCode
     * @param string|null $reasonText
     * @throws GuzzleException
     */
    private function refundOrder(
        int $storeId,
        string $fbOrderId,
        array $items,
        ?float $shippingRefundAmount,
        ?string $currencyCode,
        ?string $reasonText = null
    ) {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $this->graphAPIAdapter->refundOrder(
            $fbOrderId,
            $items,
            $shippingRefundAmount,
            $currencyCode,
            $reasonText
        );
    }

    /**
     * Private helper function that returns array of items that should be refunded
     *
     * @param CreditmemoInterface $creditmemo
     * @param OrderPaymentInterface $payment
     * @return array
     */
    private function getRefundItems(
        CreditmemoInterface $creditmemo,
        OrderPaymentInterface $payment
    ): array {
        $refundItems = [];

        foreach ($creditmemo->getItems() as $item) {
            if ($item->getQty() > 0) {
                if ($item->getDiscountAmount() == 0) {
                    $refundItems[] = [
                        'retailer_id' => $this->getRetailerId($item),
                        'item_refund_quantity' => $item->getQty(),
                    ];
                } else {
                    // @todo refunds by qty for items with discount is unavailable atm;
                    //     once it is available the else statement should be removed
                    $refundItems[] = [
                        'retailer_id' => $this->getRetailerId($item),
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
}
