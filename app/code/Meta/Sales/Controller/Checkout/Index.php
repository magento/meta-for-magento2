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

namespace Meta\Sales\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\GuestCart\GuestCartItemRepository;
use Magento\Quote\Model\GuestCart\GuestCouponManagement;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Quote\Api\Data\ProductOptionInterfaceFactory;
use Magento\ConfigurableProduct\Api\Data\ConfigurableItemOptionValueInterfaceFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Helper\OrderHelper;
use Exception;

/**
 * Handles the Meta Checkout URL endpoint.
 * Creates a quote, adds products, applies coupons, and redirects to checkout.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;

    /**
     * @var AddressFactory
     */
    private AddressFactory $quoteAddressFactory;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;
    /**
     * @var GuestCartItemRepository
     */
    private GuestCartItemRepository $guestCartItemRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    private CartItemInterfaceFactory $cartItemInterfaceFactory;

    /**
     * @var GuestCouponManagement
     */
    private GuestCouponManagement $guestCouponManagement;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var Http
     */
    private Http $httpRequest;

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ConfigurableType
     */
    private ConfigurableType $configurableType;

    /**
     * @var ProductOptionInterfaceFactory
     */
    private ProductOptionInterfaceFactory $productOptionInterfaceFactory;

    /**
     * @var ConfigurableItemOptionValueInterfaceFactory
     */
    private ConfigurableItemOptionValueInterfaceFactory $configurableItemOptionValueFactory;

    /**
     * Constructor
     *
     * @param QuoteFactory                                $quoteFactory
     * @param QuoteIdMaskFactory                          $quoteIdMaskFactory
     * @param AddressFactory                              $quoteAddressFactory
     * @param CartRepositoryInterface                     $quoteRepository
     * @param GuestCartItemRepository                     $guestCartItemRepository
     * @param CartItemInterfaceFactory                    $cartItemInterfaceFactory
     * @param GuestCouponManagement                       $guestCouponManagement
     * @param CheckoutSession                             $checkoutSession
     * @param Http                                        $httpRequest
     * @param PageFactory                                 $resultPageFactory
     * @param FBEHelper                                   $fbeHelper
     * @param OrderHelper                                 $orderHelper
     * @param ProductRepositoryInterface                  $productRepository
     * @param ConfigurableType                            $configurableType
     * @param ProductOptionInterfaceFactory               $productOptionInterfaceFactory
     * @param ConfigurableItemOptionValueInterfaceFactory $configurableItemOptionValueFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        AddressFactory $quoteAddressFactory,
        CartRepositoryInterface $quoteRepository,
        GuestCartItemRepository $guestCartItemRepository,
        CartItemInterfaceFactory $cartItemInterfaceFactory,
        GuestCouponManagement $guestCouponManagement,
        CheckoutSession $checkoutSession,
        Http $httpRequest,
        PageFactory $resultPageFactory,
        FBEHelper $fbeHelper,
        OrderHelper $orderHelper,
        ProductRepositoryInterface $productRepository,
        ConfigurableType $configurableType,
        ProductOptionInterfaceFactory $productOptionInterfaceFactory,
        ConfigurableItemOptionValueInterfaceFactory $configurableItemOptionValueFactory
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->quoteRepository = $quoteRepository;
        $this->guestCartItemRepository = $guestCartItemRepository;
        $this->cartItemInterfaceFactory = $cartItemInterfaceFactory;
        $this->guestCouponManagement = $guestCouponManagement;
        $this->checkoutSession = $checkoutSession;
        $this->httpRequest = $httpRequest;
        $this->resultPageFactory = $resultPageFactory;
        $this->fbeHelper = $fbeHelper;
        $this->orderHelper = $orderHelper;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->productOptionInterfaceFactory = $productOptionInterfaceFactory;
        $this->configurableItemOptionValueFactory = $configurableItemOptionValueFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Processes incoming request to create cart with specified products and coupon.
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws CouldNotSaveException If quote cannot be created.
     */
    public function execute()
    {
        $ebid = $this->httpRequest->getParam('ebid');
        $productsParam = $this->httpRequest->getParam('products');
        $coupon = $this->httpRequest->getParam('coupon');
        $storeId = (int)$this->orderHelper->getStoreIdByExternalBusinessId($ebid);
        $products = explode(',', $productsParam);

        $quote = $this->createQuote($storeId);
        $cartId = $this->getMaskedCartId($quote);

        foreach ($products as $productItem) {
            $this->addProductToCart($productItem, $cartId, $storeId);
        }

        if ($coupon) {
            $this->applyCouponToCart($coupon, $cartId, $storeId);
        }

        $this->checkoutSession->replaceQuote($quote);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('checkout_index_index');
        return $resultPage;
    }

    /**
     * Create a new guest quote for the specified store.
     *
     * @param int $storeId The store ID for the quote.
     *
     * @return Quote The created quote object.
     * @throws CouldNotSaveException If the quote cannot be saved.
     */
    private function createQuote(int $storeId): Quote
    {
        try {
            $quote = $this->quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->setBillingAddress($this->quoteAddressFactory->create());
            $quote->setShippingAddress($this->quoteAddressFactory->create());
            $quote->setCustomerIsGuest(true);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteRepository->save($quote);
            return $quote;
        } catch (Exception $e) {
            $this->logExceptionToMeta($e, $storeId, 'quote_save_exception');
            throw new CouldNotSaveException(__("The quote can't be created."));
        }
    }

    /**
     * Get the masked (hashed) ID for a given quote.
     *
     * @param Quote $quote The quote object.
     *
     * @return string The masked quote ID.
     */
    private function getMaskedCartId(Quote $quote): string
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($quote->getId())->save();
        return $quoteIdMask->getMaskedId();
    }

    /**
     * Add a product item to the guest cart.
     *
     * Handles simple and configurable products.
     *
     * @param string $productItem The product string in "sku:qty" format.
     * @param string $cartId      The masked cart ID.
     * @param int    $storeId     The store ID.
     *
     * @return void
     */
    private function addProductToCart(string $productItem, string $cartId, int $storeId): void
    {
        try {
            [$sku_or_id, $quantity] = explode(':', $productItem);
            $quantity = (int)$quantity;
            try {
                $product = $this->productRepository->get($sku_or_id, false, $storeId, true);
            } catch (Exception $e) {
                $product = $this->productRepository->getById($sku_or_id);
            }

            [$parentSku, $configurableOptions]
                = $this->getConfigurableOptions($product, $storeId);

            $cartItem = $this->cartItemInterfaceFactory->create();
            $cartItem->setSku($product->getSku());
            $cartItem->setQty($quantity);
            $cartItem->setQuoteId($cartId);

            if ($configurableOptions) {
                $cartItem->setSku($parentSku);
                $productOption = $this->productOptionInterfaceFactory->create();
                $extensionAttributes = $productOption->getExtensionAttributes();
                $extensionAttributes->setConfigurableItemOptions($configurableOptions);
                $productOption->setExtensionAttributes($extensionAttributes);
                $cartItem->setProductOption($productOption);
            }

            $this->guestCartItemRepository->save($cartItem);
        } catch (Exception $e) {
            $this->logExceptionToMeta(
                $e,
                $storeId,
                'error_adding_item',
                [
                    'cart_id' => $cartId,
                    // can't always rely on sku_or_id being present, as it resides in the catch block
                    'sku' => $productItem,
                    'quantity' => $quantity,
                ]
            );
        }
    }

    /**
     * Apply a coupon code to the guest cart.
     *
     * @param string $coupon  The coupon code.
     * @param string $cartId  The masked cart ID.
     * @param int    $storeId The store ID.
     *
     * @return void
     */
    private function applyCouponToCart(string $coupon, string $cartId, int $storeId): void
    {
        try {
            $this->guestCouponManagement->set($cartId, $coupon);
        } catch (Exception $e) {
            $this->logExceptionToMeta(
                $e,
                $storeId,
                'error_adding_coupon',
                [
                    'cart_id' => $cartId,
                    'coupon' => $coupon,
                ]
            );
        }
    }

    /**
     * Get configurable product options if the product is a child of a configurable product.
     *
     * @param ProductInterface $product The product object.
     * @param int              $storeId The store ID.
     *
     * @return array The parent SKU (if applicable) and configurable options.
     */
    private function getConfigurableOptions(ProductInterface $product, int $storeId): array
    {
        $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
        if (empty($parentIds)) {
            return [null, null];
        }

        $parentId = $parentIds[0];
        $parentProduct = $this->productRepository->getById($parentId, false, $storeId);
        $configurableAttributes = $this->configurableType->getConfigurableAttributes($parentProduct);
        $configurableOptions = [];

        foreach ($configurableAttributes as $attribute) {
            $attributeId = (int)$attribute->getAttributeId();
            $productAttribute = $attribute->getProductAttribute();
            $attributeValue = $product->getData($productAttribute->getAttributeCode());
            if ($attributeValue === null) {
                continue;
            }
            $optionId = $productAttribute->getSource()->getOptionId($attributeValue);

            $optionValue = $this->configurableItemOptionValueFactory->create();
            $optionValue->setOptionId($attributeId);
            $optionValue->setOptionValue($optionId);
            $configurableOptions[] = $optionValue;
        }

        return [$parentProduct->getSku(), $configurableOptions];
    }

    /**
     * Log an exception to Meta using the FBEHelper.
     *
     * @param Exception $e         The exception to log.
     * @param int       $storeId   The store ID associated with the exception.
     * @param string    $eventType Where the exception occurred.
     * @param array     $extraData Additional data to include in the log entry.
     *
     * @return void
     */
    private function logExceptionToMeta(Exception $e, int $storeId, string $eventType, array $extraData = []): void
    {
        $this->fbeHelper->logExceptionImmediatelyToMeta(
            $e,
            [
                'store_id' => $storeId,
                'event' => 'meta_checkout_url',
                'event_type' => $eventType,
                'extra_data' => $extraData,
            ]
        );
    }
}
