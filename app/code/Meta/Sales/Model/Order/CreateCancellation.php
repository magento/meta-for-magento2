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

namespace Meta\Sales\Model\Order;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Magento\Framework\DB\TransactionFactory;
use Psr\Log\LoggerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;

/**
 * Class CreateCancellation
 * Handles order cancellations from Meta Commerce Manager to Magento
 */
class CreateCancellation
{
    /**
     * Constant for the cancellation note to be added to the order
     */
    public const CANCELLATION_NOTE = 'Order Canceled from Meta.';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private $facebookOrderFactory;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var FBEHelper
     */

    protected $fbeHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateCancellation constructor
     *
     * @param OrderRepositoryInterface      $orderRepository
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param TransactionFactory            $transactionFactory
     * @param FBEHelper                     $fbeHelper
     * @param LoggerInterface               $logger
     */
    public function __construct(
        OrderRepositoryInterface      $orderRepository,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        TransactionFactory            $transactionFactory,
        FBEHelper                     $fbeHelper,
        LoggerInterface               $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->transactionFactory = $transactionFactory;
        $this->fbeHelper = $fbeHelper;
        $this->logger = $logger;
    }

    /**
     * Execute cancellation process
     *
     * @param  array $facebookOrderData
     * @param  array $facebookCancellationData
     * @return bool
     * @throws LocalizedException
     */
    public function execute(array $facebookOrderData, array $facebookCancellationData): bool
    {
        $magentoOrder = $this->getOrder($facebookOrderData);
        if (!$magentoOrder) {
            return false;
        }
        if ($this->isOrderPartiallyCanceled($magentoOrder)) {
            return false;
        }
        $cancelItems = $facebookCancellationData['items']['data'] ?? [];
        $shouldCancelOrder = $this->shouldCancelEntireOrder($magentoOrder, $cancelItems);
        $this->updateOrderQuantities($magentoOrder, $cancelItems);
        if ($shouldCancelOrder) {
            $magentoOrder->cancel();
            $magentoOrder->setStatus(Order::STATE_CANCELED);
        }
        if (isset($facebookCancellationData['cancel_reason'])) {
            $concatenatedString = '';
            if (isset($facebookCancellationData['cancel_reason']['reason_code'])) {
                $concatenatedString .= ' Reason: ' . $facebookCancellationData['cancel_reason']['reason_code'];
            }
            if (isset($facebookCancellationData['cancel_reason']['reason_description'])) {
                $concatenatedString .= ' Description: ' .
                    $facebookCancellationData['cancel_reason']['reason_description'];
            }
            $magentoOrder->addCommentToStatusHistory(self::CANCELLATION_NOTE . $concatenatedString);
        }
        $this->orderRepository->save($magentoOrder);
        return true;
    }

    /**
     * Check if the order is partially canceled
     *
     * @param  Order $order
     * @return bool
     */
    private function isOrderPartiallyCanceled(Order $order): bool
    {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyCanceled() > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve or create a Magento order based on the Facebook Order ID
     *
     * @param  array $data
     * @return Order
     * @throws GuzzleException
     * @throws LocalizedException
     */
    private function getOrder(array $data): ?Order
    {
        // Magento's "load" function will gracefully accept an invalid ID
        $facebookOrder = $this->facebookOrderFactory->create()->load($data['id'], 'facebook_order_id');
        $magentoOrderId = $facebookOrder->getMagentoOrderId();
        if ($magentoOrderId) {
            try {
                // Magento's "get" function will throw an Exception for invalid IDs
                $magentoOrder = $this->orderRepository->get($magentoOrderId);
                return $magentoOrder;
            } catch (\Exception $e) {
                $this->logger->debug($e);
            }
        }
        // In the case of any failure or missing order, simply bail and return null.
        return null;
    }

    /**
     * Determines if the entire order should be cancelled
     *
     * @param  Order $order
     * @param  array $cancelItems
     * @return bool
     */
    private function shouldCancelEntireOrder(Order $order, array $cancelItems): bool
    {
        $orderItems = $order->getAllItems();
        $totalQtyOrdered = array_sum(
            array_map(
                fn($item) => $item->getQtyOrdered(),
                $orderItems
            )
        );
        $totalQtyToCancel = array_sum(array_column($cancelItems, 'quantity'));
        return $totalQtyOrdered <= $totalQtyToCancel;
    }

    /**
     * Update the quantities of the items in the order based on the cancellation data
     *
     * @param Order $order
     * @param array $cancelItems
     */
    private function updateOrderQuantities(Order $order, array $cancelItems)
    {
        // Create a dictionary mapping SKUs to order items
        $skuToOrderItem = [];
        foreach ($order->getAllItems() as $orderItem) {
            $skuToOrderItem[$orderItem->getSku()] = $orderItem;
        }
        // Loop through items to be canceled
        foreach ($cancelItems as $cancelItem) {
            $retailerId = $cancelItem['retailer_id'] ?? null;
            $qtyToCancel = $cancelItem['quantity'] ?? 0;
            if ($retailerId === null) {
                continue;
            }
            if (isset($skuToOrderItem[$retailerId])) {
                $orderItem = $skuToOrderItem[$retailerId];
                $orderItem->setQtyCanceled($qtyToCancel);
                $orderItem->setTaxCanceled(
                    $orderItem->getTaxCanceled() +
                    $orderItem->getBaseTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
                );
                $orderItem->setHiddenTaxCanceled(
                    $orderItem->getHiddenTaxCanceled() +
                    $orderItem->getHiddenTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
                );
            } else {
                $this->fbeHelper->log(
                    sprintf(
                        "Severe issue. Item with SKU: %s was not found in Magento for cancellation",
                        $retailerId
                    )
                );
            }
        }
    }
}
