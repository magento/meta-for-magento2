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

namespace Meta\Sales\Model\Api;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Api\CreateOrderApiInterface;
use Meta\Sales\Api\Data\FacebookOrderInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Config\Source\DefaultOrderStatus;
use Meta\Sales\Model\FacebookOrder;
use Meta\Sales\Model\PaymentMethod as MetaPaymentMethod;
use Throwable;

/**
 * Create Magento order using a cart ID
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CreateOrderApi implements CreateOrderApiInterface
{
    /**
     * @var EventManagerInterface
     */
    private EventManagerInterface $eventManager;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private FacebookOrderInterfaceFactory $facebookOrderFactory;

    /**
     * @var InvoiceManagementInterface
     */
    private InvoiceManagementInterface $invoiceManagement;

    /**
     * @var TransactionFactory
     */
    private TransactionFactory $transactionFactory;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var RemoteAddress|mixed
     */
    private $remoteAddress;

    /**
     * @var RequestInterface|mixed
     */
    private $request;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * Constructor
     *
     * @param EventManagerInterface $eventManager
     * @param QuoteManagement $quoteManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param SystemConfig $systemConfig
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param InvoiceManagementInterface $invoiceManagement
     * @param TransactionFactory $transactionFactory
     * @param CheckoutSession $checkoutSession
     * @param OrderHelper $orderHelper
     * @param Authenticator $authenticator
     * @param FBEHelper $fbeHelper
     * @param RequestInterface|null $request
     * @param RemoteAddress|null $remoteAddress
     */
    public function __construct(
        EventManagerInterface         $eventManager,
        QuoteManagement               $quoteManagement,
        CartRepositoryInterface       $quoteRepository,
        SystemConfig                  $systemConfig,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        InvoiceManagementInterface    $invoiceManagement,
        TransactionFactory            $transactionFactory,
        CheckoutSession               $checkoutSession,
        OrderHelper                   $orderHelper,
        Authenticator                 $authenticator,
        FBEHelper                     $fbeHelper,
        RequestInterface              $request = null,
        RemoteAddress                 $remoteAddress = null
    ) {
        $this->eventManager = $eventManager;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->systemConfig = $systemConfig;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->invoiceManagement = $invoiceManagement;
        $this->transactionFactory = $transactionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->orderHelper = $orderHelper;
        $this->authenticator = $authenticator;
        $this->fbeHelper = $fbeHelper;
        $this->request = $request ?: ObjectManager::getInstance()
            ->get(RequestInterface::class);
        $this->remoteAddress = $remoteAddress ?: ObjectManager::getInstance()
            ->get(RemoteAddress::class);
    }

    /**
     * Get facebook order by facebook order id
     *
     * @param string $facebookOrderId
     * @return FacebookOrderInterface
     */
    private function getFacebookOrder(string $facebookOrderId): FacebookOrderInterface
    {
        /** @var FacebookOrder $facebookOrder */
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($facebookOrderId, 'facebook_order_id');

        return $facebookOrder;
    }

    /**
     * Validate quote
     *
     * @param int $quoteId
     * @param string $facebookOrderId
     * @param Quote|null $quote
     * @return void
     * @throws LocalizedException
     */
    private function validateQuote(
        int    $quoteId,
        string $facebookOrderId,
        ?Quote $quote
    ): void {
        if (!$quote->getId()) {
            throw new LocalizedException(__('Cannot find quote with ID ' . $quoteId));
        }
        if (!$quote->getStoreId()) {
            throw new LocalizedException(__('Store is not set for quote ID ' . $quoteId));
        }
        if (!$quote->getIsActive()) {
            throw new LocalizedException(__('Cannot place order. Quote ID is not active: ' . $quoteId));
        }
        $facebookOrder = $this->getFacebookOrder($facebookOrderId);
        if ($facebookOrder->getId()) {
            throw new LocalizedException(__('Cannot place order. Duplicate Meta order ID ' . $facebookOrderId));
        }
    }

    /**
     * Populate customer information in quote
     *
     * @param Quote $quote
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return void
     */
    private function populateCustomerInformation(
        Quote  $quote,
        string $email,
        string $firstName,
        string $lastName
    ): void {
        // Populate customer information
        if (!$quote->getCustomerEmail()) {
            $quote->setCustomerEmail($email);
        }
        if ($quote->getCustomerFirstname() === null
            && $quote->getCustomerLastname() === null
        ) {
            $quote->setCustomerFirstname($firstName);
            $quote->setCustomerLastname($lastName);
        }

        $remoteAddress = $this->remoteAddress->getRemoteAddress();
        if ($remoteAddress !== false) {
            $quote->setRemoteIp($remoteAddress);
            $quote->setXForwardedFor(
                $this->request->getServer('HTTP_X_FORWARDED_FOR')
            );
        }
    }

    /**
     * Create an invoice
     *
     * @param OrderInterface $magentoOrder
     * @param Transaction $transactionSave
     * @return InvoiceInterface
     * @throws LocalizedException
     */
    private function createInvoice(
        OrderInterface $magentoOrder,
        Transaction    $transactionSave
    ): InvoiceInterface {
        $invoice = $this->invoiceManagement->prepareInvoice($magentoOrder);
        $invoice->register();
        $transactionSave->addObject($invoice);
        return $invoice;
    }

    /**
     * Create a meta order record in DB
     *
     * @param string $facebookOrderId
     * @param OrderInterface $magentoOrder
     * @param string $channel
     * @param bool $buyerOptin
     * @return FacebookOrderInterface
     */
    private function createMetaOrderRecord(
        string         $facebookOrderId,
        OrderInterface $magentoOrder,
        string         $channel,
        bool           $buyerOptin
    ): FacebookOrderInterface {
        $extraData = [
            'email_remarketing_option' => $buyerOptin,
        ];
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->setFacebookOrderId($facebookOrderId)
            ->setMagentoOrderId($magentoOrder->getId())
            ->setChannel($channel)
            ->setExtraData($extraData);
        $facebookOrder->save();

        return $facebookOrder;
    }

    /**
     * Populate the checkout session details
     *
     * @param Quote $quote
     * @param OrderInterface $magentoOrder
     * @return void
     */
    private function populateCheckoutSessionDetails(
        Quote          $quote,
        OrderInterface $magentoOrder
    ): void {
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastOrderId($magentoOrder->getId());
        $this->checkoutSession->setLastRealOrderId($magentoOrder->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($magentoOrder->getStatus());
    }

    /**
     * Build the context for logging to Meta
     *
     * @param int $storeId
     * @param string $eventType
     * @param array $extraData
     * @return array
     */
    private function buildMetaLogContext(
        int    $storeId,
        string $eventType,
        array  $extraData
    ): array {
        return [
            'store_id' => $storeId,
            'event' => 'create_order',
            'event_type' => $eventType,
            'extra_data' => $extraData
        ];
    }

    /**
     * Create order
     *
     * @param string $cartId
     * @param string $orderId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param ?string $channel
     * @param bool $buyerOptin
     * @param bool $createInvoice
     * @return OrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Throwable
     */
    public function createOrder(
        string  $cartId,
        string  $orderId,
        string  $email,
        string  $firstName,
        string  $lastName,
        ?string $channel,
        bool    $buyerOptin = false,
        bool    $createInvoice = false
    ): OrderInterface {

        $extraDataForLogs = [
            'cart_id' => $cartId,
            'order_id' => $orderId,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'channel' => $channel,
            'buyerOptin' => $buyerOptin,
            'createInvoice' => $createInvoice
        ];

        $this->orderHelper->checkDynamicCheckoutConfig();
        $this->authenticator->authenticateRequest();
        $this->authenticator->validateSignature();

        /** @var Quote $quote */
        $quoteId = (int)$cartId;
        $quote = $this->quoteRepository->get($quoteId);
        $storeId = $quote->getStoreId();

        try {
            $this->validateQuote($quoteId, $orderId, $quote);
            // Set Meta's payment method ("Paid on Facebook/Instagram")
            $quote->setPaymentMethod(MetaPaymentMethod::METHOD_CODE);
            $quote->getPayment()->importData(['method' => MetaPaymentMethod::METHOD_CODE]);
            // Populate basic customer details
            $this->populateCustomerInformation($quote, $email, $firstName, $lastName);
            $this->quoteRepository->save($quote);

            // Create an order
            $this->eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);
            $magentoOrder = $this->quoteManagement->submit($quote);
            if (!($magentoOrder && $magentoOrder->getId())) {
                throw new LocalizedException(__('Unable to create an order using quote ID ' . $quoteId));
            }
            $magentoOrder->addCommentToStatusHistory("Order Imported from Meta. Meta Order ID: #{$orderId}");
            $magentoOrder->setCanSendNewEmailFlag(false);

            $transactionSave = $this->transactionFactory->create();
            $defaultStatus = $this->systemConfig->getDefaultOrderStatus($storeId);
            // Create an invoice if necessary
            if ($defaultStatus === DefaultOrderStatus::ORDER_STATUS_PROCESSING || $createInvoice) {
                $magentoOrder->setState(Order::STATE_PROCESSING)
                    ->setStatus($magentoOrder->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));

                // create invoice
                $invoice = $this->createInvoice($magentoOrder, $transactionSave);
                $magentoOrder = $invoice->getOrder();

            }
            $transactionSave->addObject($magentoOrder)->save();

            // Create Meta order record in Magento
            $facebookOrder = $this->createMetaOrderRecord($orderId, $magentoOrder, $channel, $buyerOptin);

            $this->populateCheckoutSessionDetails($quote, $magentoOrder);

            $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $magentoOrder, 'quote' => $quote]);

            $this->eventManager->dispatch('facebook_order_create_after', [
                'order' => $magentoOrder,
                'facebook_order' => $facebookOrder,
            ]);
            return $magentoOrder;
        } catch (NoSuchEntityException $e) {
            if (strpos($e->getMessage(), 'cartId') !== false) {
                $le = new LocalizedException(__(
                    "No such entity with cartId = %1",
                    $cartId
                ));
            } else {
                $le = $e;
            }
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $le,
                $this->buildMetaLogContext($storeId, "no_such_entity_exception", $extraDataForLogs)
            );
            throw $le;
        } catch (Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                $this->buildMetaLogContext($storeId, "error_creating_order", $extraDataForLogs)
            );
            throw $e;
        }
    }
}
