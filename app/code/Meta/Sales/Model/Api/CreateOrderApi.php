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

use GuzzleHttp\Exception\GuzzleException;
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
use Magento\Quote\Api\Data\AddressInterface;
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
use Meta\Sales\Api\Data\CreateOrderApiProductItemInterface;
use Meta\Sales\Api\Data\CreateOrderApiShipmentDetailsInterface;
use Meta\Sales\Api\Data\FacebookOrderInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\Sales\Helper\CommerceHelper;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Config\Source\DefaultOrderStatus;
use Meta\Sales\Model\FacebookOrder;
use Meta\Sales\Model\PaymentMethod as MetaPaymentMethod;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Throwable;

/**
 * Create Magento order using a cart ID
 *
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
     * @var CommerceHelper
     */
    private CommerceHelper $commerceHelper;

    /**
     * @var QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;

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
     * @param CommerceHelper $commerceHelper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
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
        CommerceHelper                $commerceHelper,
        QuoteIdMaskFactory            $quoteIdMaskFactory,
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
        $this->commerceHelper = $commerceHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
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
        /**
         * @var FacebookOrder $facebookOrder
         */
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($facebookOrderId, 'facebook_order_id');

        return $facebookOrder;
    }

    /**
     * Adds tax details to each item
     *
     * @param CreateOrderApiProductItemInterface[] $productItems
     * @param Quote $quote
     * @return void
     */
    private function addMetaTaxToItem(array $productItems, Quote $quote): void
    {
        $tax_map = [];
        foreach ($productItems as $item) {
            $tax_map[$item->getSku()] = ["meta_tax" => $item->getTax(), "meta_tax_rate" => $item->getTaxRate()];
        }

        // Set the tax per item
        $items = [];
        /**
         * @var Quote\Item $quoteItem
         */
        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteItem->setData("meta_tax", $tax_map[$quoteItem->getSku()]["meta_tax"]);
            $quoteItem->setData("meta_tax_rate", $tax_map[$quoteItem->getSku()]["meta_tax_rate"]);
            $items[] = $quoteItem;
        }

        $quote->setItems($items);
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
     * Populate any missing address information
     *
     * @param AddressInterface $address
     * @param AddressInterface $metaAddressInfo
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function populateAddress(
        AddressInterface $address,
        AddressInterface $metaAddressInfo
    ): void {
        if (!$address->getRegionCode()) {
            $address->setRegionCode($metaAddressInfo->getRegionCode());
        }

        if (!$address->getCountryId()) {
            $address->setCountryId($metaAddressInfo->getCountryId());
        }

        if (!$address->getStreetLine(1)) {
            $address->setStreet($metaAddressInfo->getStreet());
        }

        if (!$address->getPostcode()) {
            $address->setPostcode($metaAddressInfo->getPostcode());
        }

        if (!$address->getCity()) {
            $address->setCity($metaAddressInfo->getCity());
        }

        if (!$address->getFirstname()) {
            $address->setFirstname($metaAddressInfo->getFirstname());
        }

        if (!$address->getLastname()) {
            $address->setLastname($metaAddressInfo->getLastname());
        }

        if (!$address->getTelephone()) {
            $address->setTelephone($metaAddressInfo->getTelephone());
        }
    }

    /**
     * Populate customer information in quote
     *
     * @param Quote $quote
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param AddressInterface $shippingAddress
     * @param AddressInterface $billingAddress
     * @return void
     */
    private function populateCustomerInformation(
        Quote            $quote,
        string           $email,
        string           $firstName,
        string           $lastName,
        AddressInterface $shippingAddress,
        AddressInterface $billingAddress
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

        $this->populateAddress($quote->getShippingAddress(), $shippingAddress);
        $this->populateAddress($quote->getBillingAddress(), $billingAddress);

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
     * @param float $orderTotal
     * @param float $taxTotal
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param CreateOrderApiProductItemInterface[] $productItems
     * @param CreateOrderApiShipmentDetailsInterface $shipmentDetails
     * @param AddressInterface $billingAddress
     * @param string $channel
     * @param bool $buyerRemarketingOptIn
     * @param bool $createInvoice
     * @return OrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Throwable
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createOrder(
        string                                 $cartId,
        string                                 $orderId,
        float                                  $orderTotal,
        float                                  $taxTotal,
        string                                 $email,
        string                                 $firstName,
        string                                 $lastName,
        array                                  $productItems,
        CreateOrderApiShipmentDetailsInterface $shipmentDetails,
        AddressInterface                       $billingAddress,
        string                                 $channel,
        bool                                   $buyerRemarketingOptIn = false,
        bool                                   $createInvoice = true
    ): OrderInterface {

        $extraDataForLogs = [
            'cartId' => $cartId,
            'orderId' => $orderId,
            'orderTotal' => $orderTotal,
            'taxTotal' => $taxTotal,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'productItems' => $productItems,
            'shipmentDetails' => $shipmentDetails,
            'billingAddress' => $billingAddress,
            'channel' => $channel,
            'buyerRemarketingOptIn' => $buyerRemarketingOptIn,
            'createInvoice' => $createInvoice
        ];

        $this->authenticator->authenticateRequest();

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = (int)$quoteIdMask->getQuoteId();
        /**
         * @var Quote $quote
         */
        $quote = $this->quoteRepository->get($quoteId);
        $storeId = $quote->getStoreId();

        try {
            $this->validateQuote($quoteId, $orderId, $quote);

            // Add Meta calculated tax to item data to be used by custom tax calculation
            $this->addMetaTaxToItem($productItems, $quote);
            $quote->getShippingAddress()->setData("meta_tax", $shipmentDetails->getTax());
            $quote->getShippingAddress()->setData("meta_tax_rate", $shipmentDetails->getTaxRate());

            // Set Meta's payment method ("Paid on Facebook/Instagram")
            $quote->setPaymentMethod(MetaPaymentMethod::METHOD_CODE);
            $quote->getPayment()->importData(['method' => MetaPaymentMethod::METHOD_CODE]);

            // Validate that order is in the correct state and totals match
            $this->validateStateAndTotals($storeId, $orderId, $quote);

            // Populate basic customer details
            $this->populateCustomerInformation(
                $quote,
                $email,
                $firstName,
                $lastName,
                $shipmentDetails->getShippingAddress(),
                $billingAddress
            );
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
            $facebookOrder = $this->createMetaOrderRecord($orderId, $magentoOrder, $channel, $buyerRemarketingOptIn);

            $this->populateCheckoutSessionDetails($quote, $magentoOrder);

            $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $magentoOrder, 'quote' => $quote]);

            $this->eventManager->dispatch(
                'facebook_order_create_after',
                [
                    'order' => $magentoOrder,
                    'facebook_order' => $facebookOrder,
                ]
            );
            return $magentoOrder;
        } catch (NoSuchEntityException $e) {
            if (strpos($e->getMessage(), 'cartId') !== false) {
                $le = new LocalizedException(
                    __(
                        "No such entity with cartId = %1",
                        $cartId
                    )
                );
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

    /**
     * Validate that the meta order is in the correct state
     *
     * Confirms that the totals in the magento order match the totals in meta
     *
     * @param int $storeId
     * @param string $metaOrderId
     * @param Quote $quote
     * @return void
     * @throws GuzzleException
     * @throws LocalizedException
     */
    private function validateStateAndTotals(
        int    $storeId,
        string $metaOrderId,
        Quote  $quote
    ) {
        $magentoTotals = [];
        foreach ($quote->getTotals() as $total) {
            $magentoTotals[$total->getCode()] = $total['value'];
        }

        $metaOrderDetails = $this->commerceHelper->getOrderDetails($storeId, $metaOrderId);

        // Validate order state
        if (strcasecmp("FB_PROCESSING", $metaOrderDetails['order_status']['state']) != 0) {
            $le = new LocalizedException(__('Meta order is not in the FB_PROCESSING state'));
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $le,
                $this->buildMetaLogContext(
                    $storeId,
                    "mismatched_totals",
                    [
                        'metaOrderId' => $metaOrderId,
                        'metaOrderState' => $metaOrderDetails['order_status']['state']
                    ]
                )
            );
            throw $le;
        }

        // Validate order totals
        $metaTotals = [
            'subtotal' => $metaOrderDetails['estimated_payment_details']['subtotal']['items']['amount'],
            'shipping' => $metaOrderDetails['estimated_payment_details']['subtotal']['shipping']['amount'],
            'tax' => $metaOrderDetails['estimated_payment_details']['tax']['amount'],
            'grand_total' => $metaOrderDetails['estimated_payment_details']['total_amount']['amount']
        ];

        foreach (['subtotal', 'shipping', 'tax', 'grand_total'] as $code) {
            if ($metaTotals[$code] != $magentoTotals[$code]) {
                $le = new LocalizedException(
                    __(
                        $code . ' of ' . $metaTotals[$code] . ' in the Meta order does not match ' .
                        $code . ' of ' . $magentoTotals[$code] . ' in the Magento order'
                    )
                );

                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $le,
                    $this->buildMetaLogContext(
                        $storeId,
                        "mismatched_totals",
                        ['magentoTotals' => $magentoTotals, 'metaTotals' => $metaTotals]
                    )
                );

                throw $le;
            }
        }
    }
}
