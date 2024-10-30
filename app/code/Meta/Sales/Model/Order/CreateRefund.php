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
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\CreditmemoService;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface;

/**
 * Create refund from facebook api data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CreateRefund
{
    public const CREDIT_MEMO_NOTE = "Refunded from Meta Commerce Manager";

    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private $facebookOrderFactory;

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CreateOrder
     */
    protected $createOrder;

    /**
     * @var CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var InvoiceManagementInterface
     */
    protected $invoiceManagement;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * RefundProcessor constructor.
     *
     * @param CreditmemoFactory             $creditMemoFactory
     * @param OrderRepositoryInterface      $orderRepository
     * @param CreateOrder                   $createOrder
     * @param CreditmemoService             $creditMemoService
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param Invoice                       $invoice
     * @param InvoiceManagementInterface    $invoiceManagement
     * @param TransactionFactory            $transactionFactory
     * @param FormatInterface               $localeFormat
     * @param LoggerInterface               $logger
     */
    public function __construct(
        CreditmemoFactory             $creditMemoFactory,
        OrderRepositoryInterface      $orderRepository,
        CreateOrder                   $createOrder,
        CreditmemoService             $creditMemoService,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        Invoice                       $invoice,
        InvoiceManagementInterface    $invoiceManagement,
        TransactionFactory            $transactionFactory,
        FormatInterface               $localeFormat,
        LoggerInterface               $logger
    ) {
        $this->creditMemoFactory = $creditMemoFactory;
        $this->createOrder = $createOrder;
        $this->creditMemoService = $creditMemoService;
        $this->orderRepository = $orderRepository;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->invoice = $invoice;
        $this->invoiceManagement = $invoiceManagement;
        $this->localeFormat = $localeFormat;
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
    }

    /**
     * Processes the refund data from Facebook and creates a credit memo in Magento.
     *
     * @param  array $facebookOrderData
     * @param  array $facebookRefundData
     * @param  int   $storeId
     * @throws LocalizedException
     */
    public function execute(array $facebookOrderData, array $facebookRefundData, int $storeId)
    {
        $magentoOrder = $this->getOrCreateOrder($facebookOrderData, $storeId);

        $orderCreditMemos = $magentoOrder->getCreditmemosCollection();
        if ($orderCreditMemos->getSize() > 0) {
            // For now, we will only support the first return on an order.
            // TODO if sellers need, support multi-stage returns.
            return;
        }
        // Initialize qtys to zero and create a SKU to order item mapping
        $qtys = [];
        $skuToOrderItem = [];
        foreach ($magentoOrder->getAllItems() as $orderItem) {
            $sku = $orderItem->getSku();
            $qtys[$sku] = 0;
            $skuToOrderItem[$sku] = $orderItem;
        }

        // If 'items' is set in the Facebook refund data, update qtys accordingly
        if (isset($facebookRefundData['items'])) {
            $refundItems = $facebookRefundData['items']['data'];

            foreach ($refundItems as $item) {
                $retailerId = $item['retailer_id'];

                if (isset($qtys[$retailerId])) {
                    // Update the quantity in qtys to be the ordered quantity for that item
                    $orderItem = $skuToOrderItem[$retailerId];
                    $qtys[$retailerId] = $orderItem->getQtyOrdered();
                    // Set the refunded quantity on the order to 'all' for this item
                    $orderItem->setQtyRefunded($orderItem->getQtyOrdered());
                }
            }
        }

        $refundAmount = $facebookRefundData['refund_amount'];

        // Adjust the Invoice
        $invoice = $magentoOrder->getInvoiceCollection()->getLastItem();
        if (!$invoice->getId()) {
            $invoice = $this->createInvoice($magentoOrder);
        }

        $invoice = $this->invoice->loadByIncrementId($invoice->getIncrementId());
        $total = (float)$refundAmount['total'];
        $subtotal = (float)$refundAmount['subtotal'];
        $shipping = (float)$refundAmount['shipping'];
        $tax = (float)$refundAmount['tax'];
        // Meta will sometimes allow sellers to deduct an arbitrary amount,
        // not linked to shipping or products. This is not returned by our API;
        // we need to calculate it by summing subtotals, shipping and tax. Any extra discount is a fee.
        $orderTotal = max($subtotal + $shipping - $tax, $total);
        $shippingDeduction = $orderTotal - $total;

        $creditMemo = $this->creditMemoFactory->createByOrder($magentoOrder, ['qtys' => $qtys]);

        // Attach the adjusted invoice to the credit memo
        $creditMemo->setInvoice($invoice);

        $creditMemo->setGrandTotal($this->localeFormat->getNumber($orderTotal));
        $creditMemo->setBaseGrandTotal($this->localeFormat->getNumber($orderTotal));
        $creditMemo->setShippingAmount($this->localeFormat->getNumber($shipping));
        $creditMemo->setBaseShippingAmount($this->localeFormat->getNumber($shipping));
        // Refund Tax is passed by meta as negative, here should be positive
        $creditMemo->setTaxAmount($this->localeFormat->getNumber(-$tax));
        $creditMemo->setBaseTaxAmount($this->localeFormat->getNumber(-$tax));
        $creditMemo->setSubtotal($this->localeFormat->getNumber($subtotal));
        $creditMemo->setBaseSubtotal($this->localeFormat->getNumber($subtotal));
        $creditMemo->setAdjustmentNegative($this->localeFormat->getNumber($shippingDeduction));
        $creditMemo->addComment(self::CREDIT_MEMO_NOTE, false, false);
        try {
            // Adding an invoice to order can sometimes cause it to incorrectly
            // restrict the maximum refund cap allowed by Meta. There's likely a more graceful way
            // to do this -- TODO, revisit this code.
            $magentoOrder->setBaseTotalPaid($orderTotal);
            $magentoOrder->setTaxRefunded($this->localeFormat->getNumber(-$tax));
            $this->creditMemoService->refund($creditMemo, true, false);
        } catch (\Exception $exception) {
            $this->logger->debug(
                $exception->getMessage(),
                ['exception' => $exception, 'trace' => $exception->getTraceAsString()]
            );
        }

        $magentoOrder->setStatus(Order::STATE_CLOSED);
        $this->orderRepository->save($magentoOrder);
    }

    /**
     * Retrieve or create a Magento order based on Facebook Order ID.
     *
     * @param  array $data
     * @param  int   $storeId
     * @return Order
     * @throws LocalizedException
     * @throws GuzzleException
     */
    private function getOrCreateOrder(array $data, int $storeId): Order
    {
        $facebookOrder = $this->facebookOrderFactory->create()->load($data['id'], 'facebook_order_id');
        $magentoOrderId = $facebookOrder->getMagentoOrderId();
        if ($magentoOrderId) {
            $magentoOrder = $this->orderRepository->get($magentoOrderId);
            return $magentoOrder;
        }
        return $this->createOrder->execute($data, $storeId);
    }

    /**
     * Retrieve or create a Magento order based on Facebook Order ID.
     *
     * @param  Order $order
     * @return Invoice
     * @throws LocalizedException
     */
    private function createInvoice($order): Invoice
    {
        $invoice = $this->invoiceManagement->prepareInvoice($order);
        $invoice->register();
        $order = $invoice->getOrder();
        $transactionSave = $this->transactionFactory->create();
        $transactionSave->addObject($invoice)->addObject($order)->save();
        return $invoice;
    }
}
