<?php

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\AAMSettingsFields;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Helper\ServerEventFactory;
use Meta\Conversion\Api\TrackerInterface;
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Sales\Model\OrderRepository;

class Purchase implements TrackerInterface
{
    private const EVENT_TYPE = "Purchase";
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var ServerSideHelper
     */
    private ServerSideHelper $serverSideHelper;

    /**
     * @var ServerEventFactory
     */
    private ServerEventFactory $serverEventFactory;

    /**
     * @var CustomerMetadataInterface
     */
    private CustomerMetadataInterface $customerMetadata;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * @var OrderRepository
     */

    private OrderRepository $orderRepository;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param ServerSideHelper $serverSideHelper
     * @param ServerEventFactory $serverEventFactory
     * @param CustomerMetadataInterface $customerMetadata
     * @param PricingHelper $pricingHelper
     * @param OrderRepository $orderRepository
     * @param Escaper $escaper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        ServerSideHelper $serverSideHelper,
        ServerEventFactory $serverEventFactory,
        CustomerMetadataInterface $customerMetadata,
        PricingHelper $pricingHelper,
        OrderRepository $orderRepository,
        Escaper $escaper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->magentoDataHelper = $magentoDataHelper;
        $this->serverSideHelper = $serverSideHelper;
        $this->serverEventFactory = $serverEventFactory;
        $this->customerMetadata = $customerMetadata;
        $this->pricingHelper = $pricingHelper;
        $this->orderRepository = $orderRepository;
        $this->escaper = $escaper;
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @inheritDoc
     *
     * @param array $params
     * @return array
     */
    public function getPayload(array $params): array
    {
        try {
            $orderId = $params['lastOrder'];
            $order = $this->orderRepository->get($orderId);
            $customData = [
                'currency'     => $this->magentoDataHelper->getCurrency(),
                'value'        => $this->getOrderTotal($order),
                'content_type' => 'product',
                'num_items'    => $this->getNumItems($order),
                'content_ids'  => $this->getOrderContentIds($order),
                'contents'     => $this->getOrderContents($order),
                'content_name' => $this->getContentName($order),
                'order_id'     => (string)$this->getOrderId($order),
                'userDataFromOrder' => $this->getUserDataFromOrder($order)
            ];
        } catch (NoSuchEntityException $e) {
            return [];
        }
        return $customData;
    }

    /**
     * Return all the match keys that can be extracted from order information
     *
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getUserDataFromOrder(OrderInterface $order): array
    {
        if (!$order) {
            return [];
        }

        $userData = [];

        $userData[AAMSettingsFields::EXTERNAL_ID] =
            $order->getCustomerId();
        $userData[AAMSettingsFields::EMAIL] =
            $this->magentoDataHelper->hashValue($order->getCustomerEmail());
        $userData[AAMSettingsFields::FIRST_NAME] =
            $this->magentoDataHelper->hashValue($order->getCustomerFirstname());
        $userData[AAMSettingsFields::LAST_NAME] =
            $this->magentoDataHelper->hashValue($order->getCustomerLastname());
        $userData[AAMSettingsFields::DATE_OF_BIRTH] =
            $this->magentoDataHelper->hashValue($order->getCustomerDob() ?? '');

        if ($order->getCustomerGender()) {
            $genderId = $order->getCustomerGender();
            $userData[AAMSettingsFields::GENDER] =
                $this->magentoDataHelper->hashValue(
                    $this->customerMetadata->getAttributeMetadata('gender')
                        ->getOptions()[$genderId]->getLabel()
                );
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $userData[AAMSettingsFields::ZIP_CODE] =
                $this->magentoDataHelper->hashValue($billingAddress->getPostcode());
            $userData[AAMSettingsFields::CITY] =
                $this->magentoDataHelper->hashValue($billingAddress->getCity());
            $userData[AAMSettingsFields::PHONE] =
                $this->magentoDataHelper->hashValue($billingAddress->getTelephone());
            $userData[AAMSettingsFields::STATE] =
                $this->magentoDataHelper->hashValue($billingAddress->getRegionCode());
            $userData[AAMSettingsFields::COUNTRY] =
                $this->magentoDataHelper->hashValue($billingAddress->getCountryId());
        }

        return array_filter($userData);
    }

    /**
     * Return the id of the last order
     *
     * @param OrderInterface $order
     * @return mixed|null
     */
    private function getOrderId(OrderInterface $order)
    {
        if (!$order) {
            return null;
        } else {
            return $order->getId();
        }
    }

    /**
     * Return information about the last order items
     *
     * @param OrderInterface $order
     * @link https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/custom-data/#contents
     *
     * @return array
     */
    private function getOrderContents(OrderInterface $order): array
    {
        if (!$order) {
            return [];
        }
        $contents = [];
        /** @var Item[] $items */
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $contents[] = [
                'product_id' => $item->getSku(),
                'quantity' => (int)$item->getQtyOrdered(),
                'item_price' => $item->getPrice()
            ];
        }
        return $contents;
    }

    /**
     * Return the ids of the items by last order
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getOrderContentIds(OrderInterface $order): array
    {
        if (!$order) {
            return [];
        }
        $contentIds = [];
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $contentIds[] = $item->getSku();
        }
        return $contentIds;
    }

    /**
     * Return the last order total value
     *
     * @param OrderInterface $order
     * @return float|null
     */
    private function getOrderTotal(OrderInterface $order): ?float
    {
        if (!$order) {
            return null;
        }
        $subtotal = $order->getGrandTotal();
        if ($subtotal) {
            return $this->pricingHelper->currency($subtotal, false, false);
        } else {
            return null;
        }
    }

    /**
     * Get Num of Items
     *
     * @param OrderInterface $order
     * @return int|null
     */
    private function getNumItems(OrderInterface $order)
    {
        if (!$order) {
            return null;
        }
        return $order->getTotalQtyOrdered();
    }

    /**
     * Get Product Name
     *
     * @param OrderInterface $order
     * @return string
     */
    private function getContentName(OrderInterface $order)
    {
        $productName = [];
        if ($order) {
            $items = $order->getAllVisibleItems();
            foreach ($items as $item) {
                /** @var Product $item */
                $productName[] = $item->getName();
            }
        }
        return json_encode($productName);
    }
}
