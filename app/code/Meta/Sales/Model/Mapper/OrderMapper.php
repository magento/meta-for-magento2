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

namespace Meta\Sales\Model\Mapper;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Model\Group;
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
use Meta\Sales\Plugin\ShippingData;
use Meta\Sales\Plugin\ShippingMethodTypes;
use Meta\Sales\Helper\ShippingHelper;

/**
 * Map facebook order data to magento order
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var ShippingData
     */
    private ShippingData $shippingData;

    /**
     * @var ShippingHelper
     */
    private ShippingHelper $shippingHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param SystemConfig $systemConfig
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderPaymentInterfaceFactory $paymentFactory
     * @param OrderAddressInterfaceFactory $orderAddressFactory
     * @param OrderItemMapper $orderItemMapper
     * @param ShippingData $shippingData
     * @param ShippingHelper $shippingHelper
     */
    public function __construct(
        StoreManagerInterface        $storeManager,
        GraphAPIAdapter              $graphAPIAdapter,
        SystemConfig                 $systemConfig,
        OrderInterfaceFactory        $orderFactory,
        OrderPaymentInterfaceFactory $paymentFactory,
        OrderAddressInterfaceFactory $orderAddressFactory,
        OrderItemMapper              $orderItemMapper,
        ShippingData                 $shippingData,
        ShippingHelper               $shippingHelper
    ) {
        $this->storeManager = $storeManager;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->orderFactory = $orderFactory;
        $this->paymentFactory = $paymentFactory;
        $this->orderAddressFactory = $orderAddressFactory;
        $this->orderItemMapper = $orderItemMapper;
        $this->shippingData = $shippingData;
        $this->shippingHelper = $shippingHelper;
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
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW))
            ->setIsVirtual(false)
            ->setCustomerIsGuest(true)
            ->setCustomerEmail($billingAddress->getEmail())
            ->setCustomerFirstname($data['shipping_address']['first_name'])
            ->setCustomerLastname($data['shipping_address']['last_name'])
            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID)
            ->setCustomerNoteNotify(false)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setStoreId($storeId)
            ->setPayment($payment)
            ->setCanSendNewEmailFlag(false);

        $this->applyShippingToOrder($order, $data, $storeId);
        $this->applyItemsToOrder($order, $data, $storeId);
        $this->applyDiscountsToOrder($order, $data);
        $this->applyTotalsToOrder($order, $data, $storeId);

        $order->addCommentToStatusHistory("Order Imported from Meta. Meta Order ID: #{$facebookOrderId}");

        return $order;
    }

    /**
     * Apply shipping to magento order
     *
     * @param Order $order
     * @param array $data
     * @param int $storeId
     * @return void
     */
    private function applyShippingToOrder(Order $order, array $data, int $storeId)
    {
        $metaShippingOptionName = $data['selected_shipping_option']['name'];
        $magentoShippingReferenceID = $data['selected_shipping_option']['reference_id'];

        $shippingMethod = $this->getShippingMethod($metaShippingOptionName, $magentoShippingReferenceID, $storeId);
        $shippingDescription = $this->getShippingDescription($metaShippingOptionName, $shippingMethod, $storeId);
        // This should never happen, as it means Meta has passed a shipping method with no equivalent in Magento.
        // @todo strictly handle this edge case by canceling the entire Meta order if this happens.
        $fallbackShippingDescription = $metaShippingOptionName . " - {$shippingMethod}";

        $order
            // @todo have to set shipping method like this
            ->setShippingMethod($shippingMethod)
            ->setShippingDescription($shippingDescription ?? $fallbackShippingDescription);
    }

    /**
     * Get Magento shipping method code. For example: "flatrate_flatrate"
     *
     * @param string $shippingOptionName (possible values: "standard", "expedited", "rush")
     * @param string $shippingReferenceId
     * @param int $storeId
     * @return string|null
     * @throws LocalizedException
     */
    private function getShippingMethod(string $shippingOptionName, string $shippingReferenceId, int $storeId): ?string
    {
        if (in_array($shippingReferenceId, $this->getSyncableShippingMethodTypes())) {
            return $shippingReferenceId;
        }
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
     * Get custom shipping method label
     *
     * @param string $shippingOptionName
     * @param int $storeId
     * @return string|null
     */
    private function getShippingMethodLabel(string $shippingOptionName, int $storeId): ?string
    {
        $map = $this->systemConfig->getShippingMethodsLabelMap($storeId);
        foreach (['standard', 'expedited', 'rush'] as $item) {
            if (stripos($shippingOptionName, $item) !== false && isset($map[$item])) {
                return $map[$item];
            }
        }
        return null;
    }

    /**
     * Get ShippingMethodDescription
     *
     * @param string $metaShippingTitle
     * @param string $shippingMethod
     * @param int $storeId
     * @return string|null
     */
    private function getShippingDescription(string $metaShippingTitle, string $shippingMethod, int $storeId): ?string
    {
        $shippingLabel = $this->getShippingMethodLabel($metaShippingTitle, $storeId);
        if ($shippingLabel) {
            return $shippingLabel;
        }

        if (in_array($shippingMethod, $this->getSyncableShippingMethodTypes())) {
            $this->shippingData->setStoreId($storeId);
            [$carrier] = explode('_', $shippingMethod);
            // Possible values are string, '' and null. Falsey check is acceptable here.
            if ($carrier) {
                $shippingMethodName = $this->shippingData->getFieldFromModel($carrier, 'name');
                $shippingOptionTitle = $this->shippingData->getFieldFromModel($carrier, 'title');
                return $shippingOptionTitle . ' - ' . $shippingMethodName;
            }
        }

        return null;
    }

    /**
     * This function returns a list of shipping methods that can be synced to Meta
     *
     * @return array
     */
    public function getSyncableShippingMethodTypes(): array
    {
        return [
            ShippingMethodTypes::FREE_SHIPPING,
            ShippingMethodTypes::FLAT_RATE,
            ShippingMethodTypes::TABLE_RATE
        ];
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

        $region = $this->shippingHelper->getRegionFromCode(
            $data['shipping_address']['state'],
            $data['shipping_address']['country']
        );

        $addressData = [
            'region_id' => $region->getRegionId() ?? null,
            'region' => $region->getDefaultName() ?? $data['shipping_address']['state'],
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
        $discountAmount = 0;
        $discountNames = [];

        if ($promotionDetails) {
            foreach ($promotionDetails['data'] as $promotionDetail) {
                $targetType = $promotionDetail['target_type'] ?? null;
                if ($targetType === 'shipping') {
                    // don't treat free shipping as a discount since it is
                    // already reflected as free under shipping charges.
                } else {
                    $discountAmount -= $promotionDetail['applied_amount']['amount'];
                }

                $couponCode = $promotionDetail['coupon_code'] ?? null;
                if ($couponCode) {
                    $order->setCouponCode($couponCode);
                    $order->setCouponRuleName($promotionDetail['campaign_name']);
                    $discountNames[] = $couponCode;
                } else {
                    $discountNames[] = $promotionDetail['campaign_name'];
                }
            }

            $discountDescription = implode(', ', $discountNames);

            $order->setDiscountDescription($discountDescription);
            $order->setSubtotalWithDiscount($orderSubtotalAmount);
            $order->setBaseSubtotalWithDiscount($orderSubtotalAmount);
        }

        $order->setDiscountAmount($discountAmount);
        $order->setBaseDiscountAmount($discountAmount);

        $order->setShippingDiscountAmount(0);
        $order->setBaseShippingDiscountAmount(0);

        $order->setDiscountTaxCompensationAmount(0);
        $order->setBaseDiscountTaxCompensationAmount(0);
        $order->setShippingDiscountTaxCompensationAmount(0);
        $order->setBaseShippingDiscountTaxCompensationAmnt(0);

        // Dynamic Checkout:
        // applied_rule_ids
    }

    /**
     * Apply items to magento order from facebook order data
     *
     * @param Order $order
     * @param array $data
     * @param int $storeId
     * @return void
     */
    private function applyItemsToOrder(Order $order, array $data, int $storeId)
    {
        // @todo implement paging and tax for order items
        $items = $this->graphAPIAdapter->getOrderItems($data['id']);
        $totalQtyOrdered = 0;
        $weight = 0;
        $subtotal = 0;
        $subtotalInclTax = 0;

        foreach ($items['data'] as $item) {
            $orderItem = $this->orderItemMapper->map($item, $storeId);

            $order->addItem($orderItem);

            $totalQtyOrdered += $orderItem->getQtyOrdered();
            $weight += $orderItem->getRowWeight();
            $subtotal += $orderItem->getRowTotal();
            $subtotalInclTax += $orderItem->getRowTotalInclTax();
        }

        $order
            ->setSubtotal($subtotal)
            ->setBaseSubtotal($subtotal)
            ->setSubtotalInclTax($subtotalInclTax)
            ->setBaseSubtotalInclTax($subtotalInclTax)
            ->setTotalQtyOrdered($totalQtyOrdered)
            ->setWeight($weight);
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
        $orderTaxAmount = $data['estimated_payment_details']['tax']['amount'];
        $orderTotalAmount = $data['estimated_payment_details']['total_amount']['amount'];
        $orderTotalDue = 0;
        $baseToOrderRate = 1;
        $storeToOrderRate = 0;

        $orderShippingAmount = $data['selected_shipping_option']['price']['amount'];
        $orderShippingTaxAmount = $data['selected_shipping_option']['calculated_tax']['amount'];
        $orderShippingInclTaxAmount = $orderShippingAmount + $orderShippingTaxAmount;

        $order
            ->setGlobalCurrencyCode($currencyCode)
            ->setStoreCurrencyCode($currencyCode)
            ->setOrderCurrencyCode($currencyCode)
            ->setBaseCurrencyCode($currencyCode)
            ->setTaxAmount($orderTaxAmount)
            ->setBaseTaxAmount($orderTaxAmount)
            ->setBaseToGlobalRate($baseToOrderRate)
            ->setBaseToOrderRate($baseToOrderRate)
            ->setStoreToOrderRate($storeToOrderRate)
            ->setStoreToBaseRate($storeToOrderRate)
            ->setTotalPaid($orderTotalAmount)
            ->setBaseTotalPaid($orderTotalAmount)
            ->setTotalDue($orderTotalDue)
            ->setBaseTotalDue($orderTotalDue)
            ->setShippingAmount($orderShippingAmount)
            ->setBaseShippingAmount($orderShippingAmount)
            ->setShippingTaxAmount($orderShippingTaxAmount)
            ->setBaseShippingTaxAmount($orderShippingTaxAmount)
            ->setShippingInclTax($orderShippingInclTaxAmount)
            ->setBaseShippingInclTax($orderShippingInclTaxAmount)
            ->setGrandTotal($orderTotalAmount)
            ->setBaseGrandTotal($orderTotalAmount);
    }
}
