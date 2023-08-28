<?php

declare(strict_types=1);

namespace Meta\Sales\Model\Order;

use Exception;
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
    public const CANCELLATION_NOTE = "Cancelled from Meta Commerce Manager";

    /**
     * @var CreateOrder
     */
    private $createOrder;

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
     * CreateCancellation constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param TransactionFactory $transactionFactory
     * @param CreateOrder $createOrder
     * @param FBEHelper $fbeHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface      $orderRepository,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        TransactionFactory            $transactionFactory,
        CreateOrder                   $createOrder,
        FBEHelper                     $fbeHelper,
        LoggerInterface               $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->transactionFactory = $transactionFactory;
        $this->createOrder = $createOrder;
        $this->fbeHelper = $fbeHelper;
        $this->logger = $logger;
    }

    /**
     * Execute cancellation process
     *
     * @param array $facebookOrderData
     * @param array $facebookCancellationData
     * @param int $storeId
     * @throws LocalizedException
     */
    public function execute(array $facebookOrderData, array $facebookCancellationData, int $storeId): void
    {
        $magentoOrder = $this->getOrCreateOrder($facebookOrderData, $storeId);
        if ($this->isOrderPartiallyCanceled($magentoOrder)) {
            return;
        }
        $cancelItems = $facebookCancellationData['items']['data'] ?? [];
        $shouldCancelOrder = $this->shouldCancelEntireOrder($magentoOrder, $cancelItems);
        $this->updateOrderQuantities($magentoOrder, $cancelItems);
        if (isset($facebookCancellationData['cancel_reason'])) {
            $concatenatedString = "";

            if (isset($facebookCancellationData['cancel_reason']['reason_code'])) {
                $concatenatedString .= 'Code: ' . $facebookCancellationData['cancel_reason']['reason_code'] . '. ';
            }

            if (isset($facebookCancellationData['cancel_reason']['reason_description'])) {
                $concatenatedString .= 'Description: ' .
                    $facebookCancellationData['cancel_reason']['reason_description'];
            }

            if (!empty($concatenatedString)) {
                $magentoOrder->addCommentToStatusHistory('Cancellation Details: ' . $concatenatedString);
            }
        }
        $this->orderRepository->save($magentoOrder);
    }

    /**
     * Check if the order is partially canceled
     *
     * @param Order $order
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
     * @param array $data
     * @param int $storeId
     * @return Order
     */
    private function getOrCreateOrder(array $data, int $storeId): ?Order
    {
        $facebookOrder = $this->facebookOrderFactory->create()->load($data['id'], 'facebook_order_id');
        $magentoOrderId = $facebookOrder->getMagentoOrderId();
        if ($magentoOrderId) {
            return $this->orderRepository->get($magentoOrderId);
        }
        // Assume a method exists in your CreateOrder class to create an order based on Facebook order data
        return $this->createOrder->execute($data, $storeId, true);
    }

    /**
     * Determines if the entire order should be cancelled
     *
     * @param Order $order
     * @param array $cancelItems
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
        $this->logger->debug($totalQtyOrdered);
        $this->logger->debug($totalQtyToCancel);
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
                $this->logger->debug("trying to set qty canceled to:");
                $this->logger->debug($qtyToCancel);
                $orderItem->setQtyCanceled($qtyToCancel);
                $orderItem->setTaxCanceled(
                    $orderItem->getTaxCanceled() +
                    $orderItem->getBaseTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
                );
                $orderItem->setHiddenTaxCanceled(
                    $orderItem->getHiddenTaxCanceled() +
                    $orderItem->getHiddenTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
                );
                $orderItem->save();
                $this->logger->debug("Now trying to cancel:");
                $this->logger->debug("success");
            } else {
                $this->fbeHelper->log(sprintf(
                    "Severe issue. Item with SKU: %s was not found in Magento for cancellation",
                    $retailerId
                ));
            }
        }
    }
}
