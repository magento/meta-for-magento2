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
declare(strict_types=1);

namespace Meta\Sales\Model\Mapper;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderAddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

/**
 * Map facebook order data to magento order
 */
class OrderMapper
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var OrderInterfaceFactory
     */
    private OrderInterfaceFactory $orderFactory;

    /**
     * @var OrderPaymentInterfaceFactory
     */
    private OrderPaymentInterfaceFactory $paymentFactory;

    /**
     * @var OrderAddressInterfaceFactory
     */
    private OrderAddressInterfaceFactory $orderAddressFactory;

    /**
     * @var OrderItemMapper
     */
    private OrderItemMapper $orderItemMapper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param SystemConfig $systemConfig
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderPaymentInterfaceFactory $paymentFactory
     * @param OrderAddressInterfaceFactory $orderAddressFactory
     * @param OrderItemMapper $orderItemMapper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        GraphAPIAdapter $graphAPIAdapter,
        SystemConfig $systemConfig,
        OrderInterfaceFactory $orderFactory,
        OrderPaymentInterfaceFactory $paymentFactory,
        OrderAddressInterfaceFactory $orderAddressFactory,
        OrderItemMapper $orderItemMapper
    ) {
        $this->storeManager = $storeManager;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->orderFactory = $orderFactory;
        $this->paymentFactory = $paymentFactory;
        $this->orderAddressFactory = $orderAddressFactory;
        $this->orderItemMapper = $orderItemMapper;
    }

    /**
     * Map facebook order data to a magento order
     *
     * @param array $data
     * @param int $storeId
     * @return Order
     * @throws GuzzleException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function map(array $data, int $storeId): Order
    {
        $facebookOrderId = $data['id'];
        $isDebugMode = $this->systemConfig->isDebugMode($storeId);
        $accessToken = $this->systemConfig->getAccessToken($storeId);
        $this->graphAPIAdapter
            ->setDebugMode($isDebugMode)
            ->setAccessToken($accessToken);

        $channel = ucfirst($data['channel']);
        $shippingOptionName = $data['selected_shipping_option']['name'];
        $billingAddress = $this->getOrderBillingAddress($data);
        $shippingAddress = clone $billingAddress;
        $shippingAddress
            ->setId(null)
            ->setAddressType(Order\Address::TYPE_SHIPPING)
            ->setSameAsBilling(true);

        /** @var Payment $payment */
        $payment = $this->paymentFactory->create();
        $payment->setMethod('facebook');

        /** @var Order $order */
        $order = $this->orderFactory->create();
        $order
            ->setState(Order::STATE_NEW)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW));

        $order->setCustomerIsGuest(true)
            ->setCustomerEmail($billingAddress->getEmail())
            ->setCustomerFirstname($data['shipping_address']['first_name'])
            ->setCustomerLastname($data['shipping_address']['last_name'])
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);

        $this->applyTotalsToOrder($order, $data, $storeId);

        $shippingMethod = $this->getShippingMethod($shippingOptionName, $storeId);
        $order->setStoreId($storeId)
            // @todo have to set shipping method like this
            ->setShippingMethod($shippingMethod)
            ->setShippingDescription($shippingOptionName . " / {$shippingMethod}")
            ->setPayment($payment);

        // @todo implement paging and tax for order items
        $items = $this->graphAPIAdapter->getOrderItems($facebookOrderId);
        foreach ($items['data'] as $item) {
            $order->addItem($this->orderItemMapper->map($item, $storeId));
        }

        $this->applyDiscountsToOrder($order, $data);
        $order->addCommentToStatusHistory("Imported order #{$facebookOrderId} from {$channel}.");
        $order->setCanSendNewEmailFlag(false);

        return $order;
    }

    /**
     * Get Magento shipping method code. For example: "flatrate_flatrate"
     *
     * @param string $shippingOptionName (possible values: "standard", "expedited", "rush")
     * @param int $storeId
     * @return string|null
     * @throws LocalizedException
     */
    private function getShippingMethod(string $shippingOptionName, int $storeId): ?string
    {
        $map = $this->systemConfig->getShippingMethodsMap($storeId);
        foreach (['standard', 'expedited', 'rush'] as $item) {
            if (stripos($shippingOptionName, $item) !== false && isset($map[$item])) {
                return $map[$item];
            }
        }
        throw new LocalizedException(
            __('Cannot map shipping method. Make sure mapping is defined in system configuration.')
        );
    }

    /**
     * Create a magento order billing address from facebook order data
     *
     * @param array $data
     * @return Order\Address
     */
    private function getOrderBillingAddress(array $data): Order\Address
    {
        $street = isset($data['shipping_address']['street2'])
            ? [$data['shipping_address']['street1'], $data['shipping_address']['street2']]
            : $data['shipping_address']['street1'];

        $addressData = [
            'region' => $data['shipping_address']['state'] ?? null,
            'postcode' => $data['shipping_address']['postal_code'],
            'firstname' => $data['shipping_address']['first_name'],
            'lastname' => $data['shipping_address']['last_name'],
            'street' => $street,
            'city' => $data['shipping_address']['city'],
            'email' => $data['buyer_details']['email'],
            'telephone' => '0', // is required by magento
            'country_id' => $data['shipping_address']['country'] // maps 1:1
        ];

        /** @var Order\Address $billingAddress */
        $billingAddress = $this->orderAddressFactory->create(['data' => $addressData]);
        $billingAddress->setAddressType(Order\Address::TYPE_BILLING);

        return $billingAddress;
    }

    /**
     * Apply discounts to magento order
     *
     * @param Order $order
     * @param array $data
     * @return void
     */
    private function applyDiscountsToOrder(Order $order, array $data)
    {
        $promotionDetails = $data['promotion_details'] ?? null;
        $orderSubtotalAmount = $data['estimated_payment_details']['subtotal']['items']['amount'];

        if ($promotionDetails) {
            $discountAmount = 0;
            $couponCodes = [];
            foreach ($promotionDetails['data'] as $promotionDetail) {
                $discountAmount -= $promotionDetail['applied_amount']['amount'];
                $couponCodes[] = sprintf(
                    '[%s] %s',
                    ucfirst($promotionDetail['sponsor']),
                    $promotionDetail['campaign_name']
                );
            }
            $discountDescription = null;
            if (!empty($couponCodes)) {
                $discountDescription = implode(", ", $couponCodes);
            }
            $order->setDiscountAmount($discountAmount);
            $order->setBaseDiscountAmount($discountAmount);
            $order->setSubtotalWithDiscount($orderSubtotalAmount);
            $order->setBaseSubtotalWithDiscount($orderSubtotalAmount);
            if ($discountDescription) {
                $order->setDiscountDescription($discountDescription);
            }
        }
    }

    /**
     * Apply totals to magento order from facebook order data
     *
     * @param Order $order
     * @param array $data
     * @param int $storeId
     * @return void
     * @throws NoSuchEntityException
     */
    private function applyTotalsToOrder(Order $order, array $data, int $storeId)
    {
        $currencyCode = $this->storeManager->getStore($storeId)->getCurrentCurrencyCode();
        $orderSubtotalAmount = $data['estimated_payment_details']['subtotal']['items']['amount'];
        $orderTaxAmount = $data['estimated_payment_details']['tax']['amount'];
        $orderTotalAmount = $data['estimated_payment_details']['total_amount']['amount'];

        $order
            ->setOrderCurrencyCode($currencyCode)
            ->setBaseCurrencyCode($currencyCode)
            ->setGlobalCurrencyCode($currencyCode)
            ->setStoreCurrencyCode($currencyCode)
            ->setSubtotal($orderSubtotalAmount)
            ->setTaxAmount($orderTaxAmount)
            ->setGrandTotal($orderTotalAmount)
            ->setBaseTotalPaid($orderTotalAmount)
            ->setTotalPaid($orderTotalAmount)
            ->setBaseSubtotal($orderSubtotalAmount)
            ->setBaseGrandTotal($orderTotalAmount)
            ->setBaseShippingAmount($data['selected_shipping_option']['price']['amount'])
            ->setShippingTaxAmount($data['selected_shipping_option']['calculated_tax']['amount'])
            ->setShippingAmount($data['selected_shipping_option']['price']['amount']);
    }
}
