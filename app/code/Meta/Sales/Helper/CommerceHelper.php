<?php

namespace Meta\Sales\Helper;

use Exception;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\Catalog\Helper\Product\Identifier as ProductIdentifier;
use Meta\Sales\Model\Config\Source\DefaultOrderStatus;
use Meta\Sales\Model\FacebookOrder;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as AttributeOptionCollection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;

use Psr\Log\LoggerInterface;

class CommerceHelper extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var OrderManagement
     */
    private $orderManagement;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var GraphAPIAdapter
     */
    private $graphAPIAdapter;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var OrderExtensionFactory
     */
    protected $orderExtensionFactory;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    protected $facebookOrderFactory;

    /**
     * @var ProductIdentifier
     */
    private $productIdentifier;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ConfigurableType
     */
    private $configurableType;

    /**
     * @var AttributeOptionCollection
     */
    private $attributeOptionCollection;

    private $storeId;

    private $pageId;

    private $orderIds = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $ordersPulledTotal = 0;

    /**
     * @var Order[]
     */
    private $ordersCreated = [];

    /**
     * @var array
     */
    private $exceptions = [];

    /**
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param OrderManagement $orderManagement
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param OrderService $orderService
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param ProductIdentifier $productIdentifier
     * @param ProductRepository $productRepository
     * @param ConfigurableType $configurableType
     * @param AttributeOptionCollection $attributeOptionCollection
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        OrderManagement $orderManagement,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        GraphAPIAdapter $graphAPIAdapter,
        OrderService $orderService,
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        OrderExtensionFactory $orderExtensionFactory,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        ProductIdentifier $productIdentifier,
        ProductRepository $productRepository,
        ConfigurableType $configurableType,
        AttributeOptionCollection $attributeOptionCollection
    ) {
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->orderManagement = $orderManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->productIdentifier = $productIdentifier;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->attributeOptionCollection = $attributeOptionCollection;
        $this->storeId = $this->systemConfig->getStoreManager()->getDefaultStoreView()->getId();
        $this->pageId = $this->systemConfig->getPageId();
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        $this->pageId = $this->systemConfig->getPageId($storeId);
        $this->graphAPIAdapter->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));
        return $this;
    }

    /**
     * Get Magento shipping method code. For example: "flatrate_flatrate"
     *
     * @param string $shippingOptionName (possible values: "standard", "expedited", "rush")
     * @param array $shippingAddressData
     * @return mixed
     * @throws LocalizedException
     */
    public function getShippingMethod($shippingOptionName, $shippingAddressData = [])
    {
        $map = $this->systemConfig->getShippingMethodsMap($this->storeId);
        foreach (['standard', 'expedited', 'rush'] as $item) {
            if (stripos($shippingOptionName, $item) !== false && isset($map[$item])) {
                return $map[$item];
            }
        }
        throw new LocalizedException(__('Cannot map shipping method. Make sure mapping is defined in system configuration.'));
    }

    /**
     * Get configurable product options such as size and color
     *
     * @param ProductInterface $product
     * @param OrderItem $orderItem
     * @return array|null
     */
    private function getProductOptions(ProductInterface $product, OrderItem $orderItem)
    {
        $configurableProducts = $this->configurableType->getParentIdsByChild($product->getId());
        if (!isset($configurableProducts[0])) {
            return null;
        }
        $parentId = $configurableProducts[0];
        try {
            $parentProduct = $this->productRepository->getById($parentId, false, $product->getStoreId());
            $configurableAttributes = $this->configurableType->getConfigurableAttributes($parentProduct);

            $superAttributes = [];
            $attributesInfo = [];

            foreach ($configurableAttributes as $attribute) {
                $attributeId = (int)$attribute->getAttributeId();
                $productAttribute = $attribute->getProductAttribute();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());
                $optionId = $productAttribute->getSource()->getOptionId($attributeValue);
                $optionText = $productAttribute->getSource()->getOptionText($attributeValue);
                $superAttributes[$attributeId] = $optionId;
                $attributesInfo[] = [
                    'label' => __($productAttribute->getStoreLabel()),
                    'value' => $optionText,
                    'option_id' => $attributeId,
                    'option_value' => $optionId,
                ];
            }

            return [
                'info_buyRequest' => [
                    'qty' => $orderItem->getQtyOrdered(),
                    'super_attribute' => $superAttributes,
                ],
                'attributes_info' => $attributesInfo,
                'simple_sku' => $product->getSku(),
                'simple_name' => $product->getName(),
            ];
        } catch (Exception $e) {
            $this->logger->critical($e);
            return null;
        }
    }

    /**
     * @param $fbProductId
     * @return array|bool|mixed|object
     */
    private function getPriceBeforeDiscount($fbProductId)
    {
        try {
            $productInfo = $this->graphAPIAdapter->getProductInfo($fbProductId);
            if ($productInfo && array_key_exists('price', $productInfo)) {
                //this returns amount without $, ex: $100.00 -> 100.00
                return substr($productInfo['price'], 1);
            }
        } catch (GuzzleException $e) {
            $this->exceptions[] = $e->getMessage();
            $this->logger->critical($e->getMessage());
        }
        return false;
    }

    /**
     * @param $item
     * @return OrderItem
     * @throws LocalizedException
     */
    protected function getOrderItem(array $item)
    {
        $product = $this->productIdentifier->getProductByFacebookRetailerId($item['retailer_id']);
        $pricePerUnit = $item['price_per_unit']['amount'];

        $originalPrice = $this->getPriceBeforeDiscount($item['product_id']) ?? $pricePerUnit;

        $quantity = $item['quantity'];
        $taxAmount = $item['tax_details']['estimated_tax']['amount'];

        $rowTotal = $pricePerUnit * $quantity;
        $promotionDetails = $item['promotion_details']['data'] ?? null;
        $discountAmount = 0;
        if ($promotionDetails) {
            foreach ($promotionDetails as $promotionDetail) {
                if ($promotionDetail['target_granularity'] === 'order_level') {
                    $discountAmount += $promotionDetail['applied_amount']['amount'];
                }
            }
        }

        /** @var OrderItem $orderItem */
        $orderItem = $this->objectManager->create(OrderItem::class);
        $orderItem->setProductId($product->getId())
            ->setSku($product->getSku())
            ->setName($product->getName())
            ->setQtyOrdered($quantity)
            ->setBasePrice($originalPrice)
            ->setOriginalPrice($originalPrice)
            ->setPrice($originalPrice)
            ->setTaxAmount($taxAmount)
            ->setRowTotal($rowTotal)
            ->setDiscountAmount($discountAmount)
            ->setBaseDiscountAmount($discountAmount)
            ->setProductType($product->getTypeId());
        if ($rowTotal != 0) {
            $orderItem->setTaxPercent(round(($taxAmount / $rowTotal) * 100, 2));
        }
        $productOptions = $this->getProductOptions($product, $orderItem);
        if ($productOptions) {
            $orderItem->setProductOptions($productOptions);
        }
        return $orderItem;
    }

    /**
     * Create order without a quote to honor FB totals and tax calculations
     *
     * @param $data
     * @return Order
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function createOrder($data)
    {
        $storeId = $this->storeId ?? $this->systemConfig->getStoreId();

        $facebookOrderId = $data['id'];
        /** @var FacebookOrder $facebookOrder */
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($facebookOrderId, 'facebook_order_id');

        if ($facebookOrder->getId()) {
            $msg = __(sprintf('Order with Facebook ID %s already exists in Magento', $facebookOrderId));
            throw new LocalizedException($msg);
        }

        $street = isset($data['shipping_address']['street2'])
            ? [$data['shipping_address']['street1'], $data['shipping_address']['street2']]
            : $data['shipping_address']['street1'];

        $objectManager = $this->objectManager;
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
        $promotionDetails = $data['promotion_details'] ?? null;
        $channel = ucfirst($data['channel']);
        $shippingOptionName = $data['selected_shipping_option']['name'];

        /** @var Order\Address $billingAddress */
        $billingAddress = $objectManager->create(Order\Address::class, ['data' => $addressData]);
        $billingAddress->setAddressType(Order\Address::TYPE_BILLING);

        /** @var Order\Address $shippingAddress */
        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType(Order\Address::TYPE_SHIPPING)->setSameAsBilling(true);

        // __________________
        /** @var Payment $payment */
        $payment = $this->objectManager->create(Payment::class);
        $payment->setMethod('facebook');

        $orderSubtotalAmount = $data['estimated_payment_details']['subtotal']['items']['amount'];
        $orderTaxAmount = $data['estimated_payment_details']['tax']['amount'];
        $orderTotalAmount = $data['estimated_payment_details']['total_amount']['amount'];
        $customerEmail = $billingAddress->getEmail();

        /** @var Order $order */
        $order = $objectManager->create(Order::class);

        $order->setState(Order::STATE_NEW)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW));

        $currencyCode = $this->systemConfig->getStoreManager()->getStore($storeId)->getCurrentCurrencyCode();

        $order->setCustomerIsGuest(true)
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
            ->setCustomerEmail($customerEmail)
            ->setCustomerFirstname($data['shipping_address']['first_name'])
            ->setCustomerLastname($data['shipping_address']['last_name'])
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);
        $order->setBaseShippingAmount($data['selected_shipping_option']['price']['amount']);
        $order->setShippingTaxAmount($data['selected_shipping_option']['calculated_tax']['amount']);
        $order->setShippingAmount($data['selected_shipping_option']['price']['amount']);

        $shippingMethod = $this->getShippingMethod($shippingOptionName, $addressData);
        $order->setStoreId($storeId)
            // @todo have to set shipping method like this
            ->setShippingMethod($shippingMethod)
            ->setShippingDescription($shippingOptionName . " / {$shippingMethod}")
            ->setPayment($payment);

        // @todo implement paging and tax for order items
        $items = $this->graphAPIAdapter->getOrderItems($facebookOrderId);
        foreach ($items['data'] as $item) {
            $order->addItem($this->getOrderItem($item));
        }

        if ($promotionDetails) {
            $discountAmount = 0;
            $couponCodes = [];
            foreach ($promotionDetails['data'] as $promotionDetail) {
                $discountAmount -= $promotionDetail['applied_amount']['amount'];
                $couponCodes[] = sprintf('[%s] %s', ucfirst($promotionDetail['sponsor']), $promotionDetail['campaign_name']);
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
        $order->addCommentToStatusHistory("Imported order #{$facebookOrderId} from {$channel}.");
        $order->setCanSendNewEmailFlag(false);

        $this->orderManagement->place($order);

        $defaultStatus = $this->systemConfig->getDefaultOrderStatus($this->storeId);
        if ($defaultStatus === DefaultOrderStatus::ORDER_STATUS_PROCESSING) {
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));

            // create invoice
            $orderService = $this->objectManager->create(InvoiceManagementInterface::class);
            /** @var Order\Invoice $invoice */
            $invoice = $orderService->prepareInvoice($order);
            $invoice->register();
            $order = $invoice->getOrder();
            $transactionSave = $this->objectManager->create(Transaction::class);
            $transactionSave->addObject($invoice)->addObject($order)->save();
        }

        $extraData = [
            'email_remarketing_option' => $data['buyer_details']['email_remarketing_option'],
        ];

        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->setFacebookOrderId($facebookOrderId)
            ->setMagentoOrderId($order->getId())
            ->setChannel($channel)
            ->setExtraData($extraData);
        $facebookOrder->save();

        $this->_eventManager->dispatch('facebook_order_create_after', [
            'order' => $order,
            'facebook_order' => $facebookOrder,
        ]);

        $this->orderIds[$order->getIncrementId()] = $facebookOrderId;
        return $order;
    }

    /**
     * @param false|string $cursorAfter
     * @throws GuzzleException
     */
    public function pullOrders($cursorAfter = false)
    {
        $ordersData = $this->graphAPIAdapter->getOrders($this->pageId, $cursorAfter);

        $this->ordersPulledTotal += count($ordersData['data']);

        foreach ($ordersData['data'] as $orderData) {
            try {
                $this->ordersCreated[] = $this->createOrder($orderData);
            } catch (Exception $e) {
                $this->exceptions[] = $e->getMessage();
                $this->logger->critical($e->getMessage());
            }
        }
        if (!empty($this->orderIds)) {
            $this->graphAPIAdapter->acknowledgeOrders($this->pageId, $this->orderIds);
            $this->orderIds = [];
        }

        if (isset($ordersData['paging']['next'])) {
            $this->pullOrders($ordersData['paging']['cursors']['after']);
        }
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function pullPendingOrders()
    {
        $this->ordersPulledTotal = 0;
        $this->ordersCreated = [];
        $this->exceptions = [];
        $this->pullOrders();
        return [
            'total_orders_pulled' => $this->ordersPulledTotal,
            'total_orders_created' => count($this->ordersCreated),
            'exceptions' => $this->exceptions
        ];
    }

    /**
     * @param $fbOrderId
     * @param $items
     * @param $trackingInfo
     * @param array $fulfillmentAddressData
     * @throws GuzzleException
     */
    public function markOrderAsShipped($fbOrderId, $items, $trackingInfo, array $fulfillmentAddressData = [])
    {
        $this->graphAPIAdapter->markOrderAsShipped($fbOrderId, $items, $trackingInfo, $fulfillmentAddressData);
    }

    /**
     * @param $fbOrderId
     * @throws GuzzleException
     */
    public function cancelOrder($fbOrderId)
    {
        $this->graphAPIAdapter->cancelOrder($fbOrderId);
    }

    /**
     * @param $fbOrderId
     * @param $items
     * @param $shippingRefundAmount
     * @param $currencyCode
     * @param null $reasonText
     * @throws GuzzleException
     */
    public function refundOrder($fbOrderId, $items, $shippingRefundAmount, $currencyCode, $reasonText = null)
    {
        $this->graphAPIAdapter->refundOrder($fbOrderId, $items, $shippingRefundAmount, $currencyCode, $reasonText = null);
    }
}
